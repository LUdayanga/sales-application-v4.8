<?php

namespace App\Http\Controllers;

use App\BusinessLocation;

use App\PurchaseLine;
use App\Variation;
use App\VariationLocationDetails;
use App\SuperAdmin;
use App\Transaction;
use App\Contact;
use App\Account;
use App\TransactionPayment;
use App\Utils\ModuleUtil;

use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Datatables;

use DB;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class StockAdjustmentController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $productUtil;
    protected $transactionUtil;
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil, ModuleUtil $moduleUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->dummyPaymentLine = ['method' => 'cash', 'amount' => 0, 'note' => '', 'card_transaction_number' => '', 'card_number' => '', 'card_type' => '', 'card_holder_name' => '', 'card_month' => '', 'card_year' => '', 'card_security' => '', 'cheque_number' => '', 'bank_account_number' => '',
        'is_return' => 0, 'transaction_no' => ''];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('purchase.view') && !auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $stock_adjustments = Transaction::join(
                'business_locations AS BL',
                'transactions.location_id',
                '=',
                'BL.id'
            )
                ->leftJoin('users as u', 'transactions.created_by', '=', 'u.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'stock_adjustment')
                // ->whereNull('transactions.sub_type')
                ->select(
                    'transactions.id',
                    'transaction_date',
                    'ref_no',
                    'BL.name as location_name',
                    'adjustment_type',
                    'sub_type',
                    'final_total',
                    'total_amount_recovered',
                    'additional_notes',
                    'transactions.id as DT_RowId',
                    DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by")
                );

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $stock_adjustments->whereIn('transactions.location_id', $permitted_locations);
            }

            $hide = '';
            $start_date = request()->get('start_date');
            $end_date = request()->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $stock_adjustments->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
                $hide = 'hide';
            }
            $location_id = request()->get('location_id');
            if (!empty($location_id)) {
                $stock_adjustments->where('transactions.location_id', $location_id);
            }

            $condition = request()->get('condition', null);
            if (!empty($condition)) {
                $stock_adjustments->where('sub_type', $condition);
            }

            return Datatables::of($stock_adjustments)
                ->addColumn('action', '<button type="button" data-href="{{  action("StockAdjustmentController@show", [$id]) }}" class="btn btn-primary btn-xs btn-modal" data-container=".view_modal"><i class="fa fa-eye" aria-hidden="true"></i> @lang("messages.view")</button>
                 &nbsp;
                    <button type="button" data-href="{{  action("StockAdjustmentController@destroy", [$id]) }}" class="btn btn-danger btn-xs delete_stock_adjustment ' . $hide . '"><i class="fa fa-trash" aria-hidden="true"></i> @lang("messages.delete")</button>')
                ->removeColumn('id')
                ->editColumn(
                    'final_total',
                    '<span class="display_currency" data-currency_symbol="true">{{$final_total}}</span>'
                )
                ->editColumn(
                    'total_amount_recovered',
                    '<span class="display_currency" data-currency_symbol="true">{{$total_amount_recovered}}</span>'
                )
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                // ->editColumn('adjustment_type', function ($row) {
                //     return __('stock_adjustment.' . $row->adjustment_type);
                // })

                ->addColumn('adjustment_type', function ($row) {
                    $super_admin = SuperAdmin::first();

                    $html = '';

                    $html .= '<span>' . __('stock_adjustment.' . $row->adjustment_type) . '</span>';

                    if($super_admin->advance_stock_adjustment != 0){
                        if ($row->sub_type == 'increase') {
                            $html .= '<button data-val="" data-href="" class="btn btn-xs btn-success btn_update_modal">Stock increase</button>';
                        } else {
                            $html .= '<button data-val="" data-href="" class="btn btn-xs btn-warning btn_update_modal">Stock decrease</button>';
                        }
                    }

                    return $html;
                })

                ->setRowAttr([
                    'data-href' => function ($row) {
                        return  action('StockAdjustmentController@show', [$row->id]);
                    }
                ])
                ->rawColumns(['final_total', 'action', 'total_amount_recovered', 'adjustment_type'])
                ->make(true);
        }

        $super_admin = SuperAdmin::first();

        return view('stock_adjustment.index')->with(compact('super_admin'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action('StockAdjustmentController@index'));
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $super_admin = SuperAdmin::first();

        return view('stock_adjustment.create')
            ->with(compact('business_locations', 'super_admin'));
    }

    public function createForAdd()
    {
        if (!auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action('StockAdjustmentController@index'));
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('stock_adjustment.create_for_add')
            ->with(compact('business_locations'));
    }

    public function createBulkReturn ($id){
        
        $business_id = request()->session()->get('user.business_id');
        $contact = Contact::where('id', $id)->first();

        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action('StockAdjustmentController@index'));
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        $payment_line = $this->dummyPaymentLine;

        $payment_types = $this->transactionUtil->payment_types(null, false, $business_id);

        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false, true);
        }

        return view('sell_bulk_return.create')
            ->with(compact('business_locations', 'contact', 'payment_line', 'payment_types', 'accounts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $super_admin = SuperAdmin::first();

            $input_data = $request->only(['location_id', 'transaction_date', 'adjustment_type', 'additional_notes', 'total_amount_recovered', 'final_total', 'ref_no']);
            $business_id = $request->session()->get('user.business_id');

            //Check if subscribed or not
            if (!$this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse(action('StockAdjustmentController@index'));
            }

            $user_id = $request->session()->get('user.id');

            $input_data['type'] = 'stock_adjustment';

            if ($super_admin->advance_stock_adjustment != 0) {
                if ($request->incOrdesc == 'decrease') {
                    $input_data['sub_type'] = 'decrease';
                } else {
                    $input_data['sub_type'] = 'increase';
                }
            }

            $input_data['business_id'] = $business_id;
            $input_data['created_by'] = $user_id;
            $input_data['transaction_date'] = $this->productUtil->uf_date($input_data['transaction_date'], true);
            $input_data['total_amount_recovered'] = $this->productUtil->num_uf($input_data['total_amount_recovered']);

            //Update reference count
            $ref_count = $this->productUtil->setAndGetReferenceCount('stock_adjustment');
            //Generate reference number
            if (empty($input_data['ref_no'])) {
                $input_data['ref_no'] = $this->productUtil->generateReferenceNumber('stock_adjustment', $ref_count);
            }

            $products = $request->input('products');

            if (!empty($products)) {
                $product_data = [];

                foreach ($products as $product) {
                    $adjustment_line = [
                        'product_id' => $product['product_id'],
                        'variation_id' => $product['variation_id'],
                        'quantity' => $this->productUtil->num_uf($product['quantity']),
                        'unit_price' => $this->productUtil->num_uf($product['unit_price'])
                    ];
                    if (!empty($product['lot_no_line_id'])) {
                        //Add lot_no_line_id to stock adjustment line
                        $adjustment_line['lot_no_line_id'] = $product['lot_no_line_id'];
                    }
                    $product_data[] = $adjustment_line;

                    if ($super_admin->advance_stock_adjustment != 0) {
                        if ($request->incOrdesc == 'decrease') {
                            //Decrease available quantity
                            $this->productUtil->decreaseProductQuantity(
                                $product['product_id'],
                                $product['variation_id'],
                                $input_data['location_id'],
                                $this->productUtil->num_uf($product['quantity'])
                            );
                        } else {
                            //Increse available quantity
                            $this->productUtil->increaseProductQuantity(
                                $product['product_id'],
                                $product['variation_id'],
                                $input_data['location_id'],
                                $this->productUtil->num_uf($product['quantity'])
                            );
                        }
                    } else {
                        //Decrease available quantity
                        $this->productUtil->decreaseProductQuantity(
                            $product['product_id'],
                            $product['variation_id'],
                            $input_data['location_id'],
                            $this->productUtil->num_uf($product['quantity'])
                        );
                    }
                }

                $stock_adjustment = Transaction::create($input_data);
                $stock_adjustment->stock_adjustment_lines()->createMany($product_data);

                //Map Stock adjustment & Purchase.
                $business = [
                    'id' => $business_id,
                    'accounting_method' => $request->session()->get('business.accounting_method'),
                    'location_id' => $input_data['location_id']
                ];

                if ($request->incOrdesc == 'decrease'){
                    $this->transactionUtil->mapPurchaseSell($business, $stock_adjustment->stock_adjustment_lines, 'stock_adjustment');
                }

                $this->transactionUtil->activityLog($stock_adjustment, 'added', null, [], false);
            }

            $output = [
                'success' => 1,
                'msg' => __('stock_adjustment.stock_adjustment_added_successfully')
            ];

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $msg = trans("messages.something_went_wrong");

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }

            $output = [
                'success' => 0,
                'msg' => $msg
            ];
        }

        return redirect('stock-adjustments')->with('status', $output);
    }


    public function storeBulkSellReturn(Request $request){
        try {
            DB::beginTransaction();

            $super_admin = SuperAdmin::first();

            $input_data = $request->only(['contact_id', 'location_id', 'transaction_date', 'adjustment_type', 'additional_notes', 'total_amount_recovered', 'ref_no']);
            $business_id = $request->session()->get('user.business_id');

            //Check if subscribed or not
            if (!$this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse(action('StockAdjustmentController@index'));
            }

            $input_data['final_total'] = $request->input('final_replace_total');
            $input_data['bulk_return_payment_amount'] = $request->input('final_total');

            $user_id = $request->session()->get('user.id');

            $input_data['type'] = 'stock_adjustment';

            $input_data['sub_type'] = 'bulk_sell_return';

            $input_data['business_id'] = $business_id;
            $input_data['created_by'] = $user_id;
            $input_data['transaction_date'] = $this->productUtil->uf_date($input_data['transaction_date'], true);
            $input_data['total_amount_recovered'] = $this->productUtil->num_uf($input_data['total_amount_recovered']);

            //Update reference count
            $ref_count = $this->productUtil->setAndGetReferenceCount('sell_return');
            //Generate reference number
            if (empty($input_data['ref_no'])) {
                $input_data['ref_no'] = $this->productUtil->generateReferenceNumber('sell_return', $ref_count);
            }

            $products = $request->input('products');

            if (!empty($products)) {
                $product_data = [];

                foreach ($products as $product) {
                    $adjustment_line = [
                        'product_id' => $product['product_id'],
                        'variation_id' => $product['variation_id'],
                        'quantity' => $this->productUtil->num_uf($product['quantity']),
                        'unit_price' => $this->productUtil->num_uf($product['unit_price']),
                        'bulk_return_quantity' => $this->productUtil->num_uf($product['return_quantity'])
                    ];
                    if (!empty($product['lot_no_line_id'])) {
                        //Add lot_no_line_id to stock adjustment line
                        $adjustment_line['lot_no_line_id'] = $product['lot_no_line_id'];
                    }
                    $product_data[] = $adjustment_line;

                        //Decrease available quantity
                        $this->productUtil->decreaseProductQuantity(
                            $product['product_id'],
                            $product['variation_id'],
                            $input_data['location_id'],
                            $this->productUtil->num_uf($product['quantity'])
                        );

                }

                $stock_adjustment = Transaction::create($input_data);
                $stock_adjustment->stock_adjustment_lines()->createMany($product_data);

                $this->transactionUtil->createPaymentLinesForBulkReturn($stock_adjustment, $request->input('payment'));

                //Update payment status
                $payment_status = $this->transactionUtil->updatePaymentStatus($stock_adjustment->id, $stock_adjustment->bulk_return_payment_amount);

                //isset customer due
                if(isset($request->enable_cutomer_due)){
                    $tp = $this->transactionUtil->payContactForBulkReturn($stock_adjustment->id, $request->input('contact_id'), $request->input('payment'));
                }

                //Map Stock adjustment & Purchase.
                $business = [
                    'id' => $business_id,
                    'accounting_method' => $request->session()->get('business.accounting_method'),
                    'location_id' => $input_data['location_id']
                ];

                $this->transactionUtil->activityLog($stock_adjustment, 'added', null, [], false);
            }

            $output = [
                'success' => 1,
                'msg' => __('Bulk sell return added successfully')
            ];

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $msg = trans("messages.something_went_wrong");

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }

            $output = [
                'success' => 0,
                'msg' => $msg
            ];
        }

        return redirect('bulk-sell-return')->with('status', $output);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!auth()->user()->can('purchase.view')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        $stock_adjustment = Transaction::where('transactions.business_id', $business_id)
            ->where('transactions.id', $id)
            ->where('transactions.type', 'stock_adjustment')
            ->with(['stock_adjustment_lines', 'location', 'business', 'stock_adjustment_lines.variation', 'stock_adjustment_lines.variation.product', 'stock_adjustment_lines.variation.product_variation', 'stock_adjustment_lines.lot_details'])
            ->first();

        $lot_n_exp_enabled = false;
        if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
            $lot_n_exp_enabled = true;
        }

        $activities = Activity::forSubject($stock_adjustment)
            ->with(['causer', 'subject'])
            ->latest()
            ->get();

        $super_admin = SuperAdmin::first();

        return view('stock_adjustment.show')
            ->with(compact('stock_adjustment', 'lot_n_exp_enabled', 'activities', 'super_admin'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Transaction  $stockAdjustment
     * @return \Illuminate\Http\Response
     */
    public function edit(Transaction $stockAdjustment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Transaction  $stockAdjustment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Transaction $stockAdjustment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('purchase.delete')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            if (request()->ajax()) {
                DB::beginTransaction();

                $super_admin = SuperAdmin::first();
                
                $stock_adjustment = Transaction::where('id', $id)
                    ->where('type', 'stock_adjustment')
                    ->with(['stock_adjustment_lines'])
                    ->first();

                //Add deleted product quantity to available quantity
                $stock_adjustment_lines = $stock_adjustment->stock_adjustment_lines;
                if (!empty($stock_adjustment_lines)) {
                    $line_ids = [];
                    foreach ($stock_adjustment_lines as $stock_adjustment_line) {

                        if($super_admin->advance_stock_adjustment != 0){
                            if ($stock_adjustment->sub_type == 'decrease') {
                                $this->productUtil->updateProductQuantity(
                                    $stock_adjustment->location_id,
                                    $stock_adjustment_line->product_id,
                                    $stock_adjustment_line->variation_id,
                                    $this->productUtil->num_f($stock_adjustment_line->quantity)
                                );
                            } else {
                                $variation = Variation::where('id', $stock_adjustment_line->variation_id)
                                    ->where('product_id', $stock_adjustment_line->product_id)
                                    ->first();
    
                                //Add quantity in VariationLocationDetails
                                $variation_location_d = VariationLocationDetails::where('variation_id', $variation->id)
                                    ->where('product_id', $stock_adjustment_line->product_id)
                                    ->where('product_variation_id', $variation->product_variation_id)
                                    ->where('location_id', $stock_adjustment->location_id)
                                    ->first();
    
                                $variation_location_d->qty_available -= $this->productUtil->num_f($stock_adjustment_line->quantity);
                                $variation_location_d->save();
                            }
                        }else{
                            $this->productUtil->updateProductQuantity(
                                $stock_adjustment->location_id,
                                $stock_adjustment_line->product_id,
                                $stock_adjustment_line->variation_id,
                                $this->productUtil->num_f($stock_adjustment_line->quantity)
                            );
                        }

                        $line_ids[] = $stock_adjustment_line->id;
                    }

                    $this->transactionUtil->mapPurchaseQuantityForDeleteStockAdjustment($line_ids);
                }
                $stock_adjustment->delete();
                
                //check bulk sell return ot not
                if($stock_adjustment->sub_type == "bulk_sell_return"){
                //delete stock adjustment account record
                DB::table('account_transactions')->where('transaction_id', $id)->delete();

                //delete customer due payment
                $get_parent_payment = TransactionPayment::where('bulk_return_transaction_id', $id)->first();
                
                if(!empty($get_parent_payment)){
                    //get child payments
                $child_payments = TransactionPayment::where('parent_id', $get_parent_payment->id)->get();
                
                //delete child payments
                foreach ($child_payments as $child_payment) {
                    $payment = TransactionPayment::findOrFail($child_payment->id);

                    if (!empty($payment->transaction_id)) {
                        TransactionPayment::deletePayment($payment);
                    } else { //advance payment
                        $adjusted_payments = TransactionPayment::where('parent_id', 
                                                $payment->id)
                                                ->get();

                        $total_adjusted_amount = $adjusted_payments->sum('amount');

                        //Get customer advance share from payment and deduct from advance balance
                        $total_customer_advance = $payment->amount - $total_adjusted_amount;
                        if ($total_customer_advance > 0) {
                            $this->transactionUtil->updateContactBalance($payment->payment_for, $total_customer_advance , 'deduct');
                        }

                        //Delete all child payments
                        foreach ($adjusted_payments as $adjusted_payment) {
                            //Make parent payment null as it will get deleted
                            $adjusted_payment->parent_id = null;
                            TransactionPayment::deletePayment($adjusted_payment);
                        }

                        //Delete advance payment
                        TransactionPayment::deletePayment($payment);
                    }
                }

                //delete parent payment account details
                DB::table('account_transactions')->where('transaction_payment_id', $get_parent_payment->id)->delete();
                }
                }

                //Remove Mapping between stock adjustment & purchase.

                $output = [
                    'success' => 1,
                    'msg' => __('stock_adjustment.delete_success')
                ];

                DB::commit();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
        }
        return $output;
    }

    /**
     * Return product rows
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */

     //this is normal adjustment
    public function getProductRow(Request $request)
    {
        if (request()->ajax()) {
            $row_index = $request->input('row_index');
            $variation_id = $request->input('variation_id');
            $location_id = $request->input('location_id');

            $business_id = $request->session()->get('user.business_id');
            $product = $this->productUtil->getDetailsFromVariation($variation_id, $business_id, $location_id);

            $product->formatted_qty_available = $this->productUtil->num_f($product->qty_available);
            $type = !empty($request->input('type')) ? $request->input('type') : 'stock_adjustment';

            //Get lot number dropdown if enabled
            $lot_numbers = [];
            if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($variation_id, $business_id, $location_id, true);
                foreach ($lot_number_obj as $lot_number) {
                    $lot_number->qty_formated = $this->productUtil->num_f($lot_number->qty_available);
                    $lot_numbers[] = $lot_number;
                }
            }
            $product->lot_numbers = $lot_numbers;
            
            $sub_units = $this->productUtil->getSubUnits($business_id, $product->unit_id, false, $product->id);
            if ($type == 'stock_transfer') {
                return view('stock_transfer.partials.product_table_row')
                    ->with(compact('product', 'row_index', 'sub_units'));
            } else {
                return view('stock_adjustment.partials.product_table_row')
                    ->with(compact('product', 'row_index', 'sub_units'));
            }
        }
    }

    //this is get product for stock adjustment for increase
    public function getProductRowForIncrease(Request $request)
    {

        if (request()->ajax()) {
            $row_index = $request->input('row_index');
            $variation_id = $request->input('variation_id');
            $location_id = $request->input('location_id');

            $business_id = $request->session()->get('user.business_id');
            $product = $this->productUtil->getDetailsFromVariationForIncrease($variation_id, $business_id, $location_id);
            $product->formatted_qty_available = $this->productUtil->num_f($product->qty_available);
            $type = !empty($request->input('type')) ? $request->input('type') : 'stock_adjustment';

            //Get lot number dropdown if enabled
            $lot_numbers = [];
            if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($variation_id, $business_id, $location_id, true);
                foreach ($lot_number_obj as $lot_number) {
                    $lot_number->qty_formated = $this->productUtil->num_f($lot_number->qty_available);
                    $lot_numbers[] = $lot_number;
                }
            }
            $product->lot_numbers = $lot_numbers;
            
            $sub_units = $this->productUtil->getSubUnits($business_id, $product->unit_id, false, $product->id);
            if ($type == 'stock_transfer') {
                return view('stock_transfer.partials.product_table_row')
                    ->with(compact('product', 'row_index', 'sub_units'));
            } else {
                if(isset($request->adjust_type)){
                    return view('stock_adjustment.partials.product_table_row_for_increase')
                    ->with(compact('product', 'row_index', 'sub_units'));
                }else{
                    return view('stock_adjustment.partials.product_table_row')
                    ->with(compact('product', 'row_index', 'sub_units'));
                }
            }
        }
    }

        //this is get product for bulk sell return
        public function getProductRowForBulkReturn(Request $request)
        {
            if (request()->ajax()) {
                $row_index = $request->input('row_index');
                $variation_id = $request->input('variation_id');
                $location_id = $request->input('location_id');
    
                $business_id = $request->session()->get('user.business_id');
                $product = $this->productUtil->getDetailsFromVariationForIncrease($variation_id, $business_id, $location_id, true);
                $product->formatted_qty_available = $this->productUtil->num_f($product->qty_available);
                $type = !empty($request->input('type')) ? $request->input('type') : 'stock_adjustment';
    
                //Get lot number dropdown if enabled
                $lot_numbers = [];
                if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                    $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($variation_id, $business_id, $location_id, true);
                    foreach ($lot_number_obj as $lot_number) {
                        $lot_number->qty_formated = $this->productUtil->num_f($lot_number->qty_available);
                        $lot_numbers[] = $lot_number;
                    }
                }
                $product->lot_numbers = $lot_numbers;
                
                $sub_units = $this->productUtil->getSubUnits($business_id, $product->unit_id, false, $product->id);
                if ($type == 'stock_transfer') {
                    return view('stock_transfer.partials.product_table_row')
                        ->with(compact('product', 'row_index', 'sub_units'));
                } else {
                    return view('sell_bulk_return.partials.product_table_row_for_bulk_return')
                        ->with(compact('product', 'row_index', 'sub_units'));
                }
            }
        }

    /**
     * Sets expired purchase line as stock adjustmnet
     *
     * @param int $purchase_line_id
     * @return json $output
     */
    public function removeExpiredStock($purchase_line_id)
    {
        if (!auth()->user()->can('purchase.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $purchase_line = PurchaseLine::where('id', $purchase_line_id)
                ->with(['transaction'])
                ->first();

            if (!empty($purchase_line)) {
                DB::beginTransaction();

                $qty_unsold = $purchase_line->quantity - $purchase_line->quantity_sold - $purchase_line->quantity_adjusted - $purchase_line->quantity_returned;
                $final_total = $purchase_line->purchase_price_inc_tax * $qty_unsold;

                $user_id = request()->session()->get('user.id');
                $business_id = request()->session()->get('user.business_id');

                //Update reference count
                $ref_count = $this->productUtil->setAndGetReferenceCount('stock_adjustment');

                $stock_adjstmt_data = [
                    'type' => 'stock_adjustment',
                    'business_id' => $business_id,
                    'created_by' => $user_id,
                    'transaction_date' => \Carbon::now()->format('Y-m-d'),
                    'total_amount_recovered' => 0,
                    'location_id' => $purchase_line->transaction->location_id,
                    'adjustment_type' => 'normal',
                    'final_total' => $final_total,
                    'ref_no' => $this->productUtil->generateReferenceNumber('stock_adjustment', $ref_count)
                ];

                //Create stock adjustment transaction
                $stock_adjustment = Transaction::create($stock_adjstmt_data);

                $stock_adjustment_line = [
                    'product_id' => $purchase_line->product_id,
                    'variation_id' => $purchase_line->variation_id,
                    'quantity' => $qty_unsold,
                    'unit_price' => $purchase_line->purchase_price_inc_tax,
                    'removed_purchase_line' => $purchase_line->id
                ];

                //Create stock adjustment line with the purchase line
                $stock_adjustment->stock_adjustment_lines()->create($stock_adjustment_line);

                //Decrease available quantity
                $this->productUtil->decreaseProductQuantity(
                    $purchase_line->product_id,
                    $purchase_line->variation_id,
                    $purchase_line->transaction->location_id,
                    $qty_unsold
                );

                //Map Stock adjustment & Purchase.
                $business = [
                    'id' => $business_id,
                    'accounting_method' => request()->session()->get('business.accounting_method'),
                    'location_id' => $purchase_line->transaction->location_id
                ];
                $this->transactionUtil->mapPurchaseSell($business, $stock_adjustment->stock_adjustment_lines, 'stock_adjustment', false, $purchase_line->id);

                DB::commit();

                $output = [
                    'success' => 1,
                    'msg' => __('lang_v1.stock_removed_successfully')
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $msg = trans("messages.something_went_wrong");

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }

            $output = [
                'success' => 0,
                'msg' => $msg
            ];
        }
        return $output;
    }
}
