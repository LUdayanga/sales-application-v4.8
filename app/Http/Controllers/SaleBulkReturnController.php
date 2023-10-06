<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Transaction;
use App\Contact;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ContactUtil;
use App\TransactionPayment;
use App\SuperAdmin;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\TransactionSellLine;
use App\Events\TransactionPaymentDeleted;
use Spatie\Activitylog\Models\Activity;
use App\Exceptions\AdvanceBalanceNotAvailable;
use App\Events\TransactionPaymentAdded;
use App\Cheque;
use App\ChequeTransaction;
use App\AccountTransaction;
use App\TaxRate;
use App\CustomerGroup;
use App\SellingPriceGroup;
use App\InvoiceScheme;
use App\TypesOfService;
use App\Account;
use App\Business;

class SaleBulkReturnController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $contactUtil;
    protected $businessUtil;
    protected $transactionUtil;
    protected $productUtil;

    public function __construct(ContactUtil $contactUtil, BusinessUtil $businessUtil, TransactionUtil $transactionUtil, ModuleUtil $moduleUtil, ProductUtil $productUtil)
    {
        $this->contactUtil = $contactUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->productUtil = $productUtil;

        $this->dummyPaymentLine = ['method' => '', 'amount' => 0, 'note' => '', 'card_transaction_number' => '', 'card_number' => '', 'card_type' => '', 'card_holder_name' => '', 'card_month' => '', 'card_year' => '', 'card_security' => '', 'cheque_number' => '', 'bank_account_number' => '',
        'is_return' => 0, 'transaction_no' => ''];

        $this->shipping_status_colors = [
            'ordered' => 'bg-yellow',
            'packed' => 'bg-info',
            'shipped' => 'bg-navy',
            'delivered' => 'bg-green',
            'cancelled' => 'bg-red',
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {

            $stock_adjustments = Transaction::join(
                'business_locations AS BL',
                'transactions.location_id',
                '=',
                'BL.id'
            )
            ->leftJoin(
                'transaction_payments AS TP',
                'transactions.id',
                '=',
                'TP.transaction_id'
            )
                ->leftJoin('users as u', 'transactions.created_by', '=', 'u.id')
                ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'stock_adjustment')
                ->where('transactions.sub_type', 'bulk_sell_return')
                ->select(
                    'transactions.id',
                    'transaction_date',
                    'transactions.contact_id',
                    'ref_no',
                    'BL.name as location_name',
                    'adjustment_type',
                    'sub_type',
                    'payment_status',
                    'final_total',
                    'bulk_return_payment_amount',
                    'total_amount_recovered',
                    'additional_notes',
                    'transactions.id as DT_RowId',
                    DB::raw('SUM(TP.amount) as amount_paid'),
                    DB::raw("CONCAT(COALESCE(contacts.supplier_business_name, ''),' ',COALESCE(contacts.first_name, ''),' ',COALESCE(contacts.last_name,'')) as customer"),
                    DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by")
                )
                ->groupBy('transactions.id');

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

            $contact_id = request()->get('contact_id', null);
            if (!empty($contact_id)) {
                $stock_adjustments->where('transactions.contact_id', $contact_id);
            }

            return Datatables::of($stock_adjustments)
            ->addColumn('action', function ($row) {
                $html = '<div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                            data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                $html .= '<li><a href="#" data-href="' . action('SaleBulkReturnController@show', [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-eye" aria-hidden="true"></i>' . __("messages.view") . '</a></li>';
                $html .= '<li><a href="#" data-href="' . action('StockAdjustmentController@destroy', [$row->id]) . '" class="delete_stock_adjustment"><i class="fas fa-trash"></i>' . __("messages.delete") . '</a></li>';             

                    $html .= '<li class="divider"></li>';
                    if ($row->payment_status != 'paid') {
                        $html .= '<li><a href="#" data-href="' . action('SaleBulkReturnController@addPayment', [$row->id]) . '" class="add_payment_modal"><i class="fas fa-money-bill-alt" aria-hidden="true"></i>' . __("purchase.add_payment") . '</a></li>';
                    }
                    
                    $html .= '<li><a href="#" data-href="' . action('TransactionPaymentController@show', [$row->id]) .
                    '" class="view_payment_modal"><i class="fas fa-money-bill-alt" aria-hidden="true" ></i>' . __("purchase.view_payments") . '</a></li>';

                $html .=  '</ul></div>';
                return $html;
            })
                ->removeColumn('id')
                ->editColumn('total_return', function ($row) {
                    $total = ($row->final_total + $row->bulk_return_payment_amount);
                    return '<span class="display_currency" data-currency_symbol="true">' . $total . '</span>';
                })
                ->editColumn(
                    'total_refund',
                    '<span class="display_currency" data-currency_symbol="true">{{$bulk_return_payment_amount}}</span>'
                )
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->addColumn('payment_due', function ($row) {
                    $due = $row->bulk_return_payment_amount - $row->amount_paid;
                    return '<span class="payment_due" data-orig-value="' . $due . '">' . $this->transactionUtil->num_f($due, true) . '</span>';
                })
                ->editColumn('payment_status', function ($row) {
                    if ($row->payment_status == 'due') {
                        return '<span class="label label-warning">Due</span>';
                    } elseif ($row->payment_status == 'partial') {
                        return '<span class="label label-info">Partial</span>';
                    } elseif ($row->payment_status == 'paid') {
                        return '<span class="label label-success">Paid</span>';
                    } else {
                        return '<span class="label label-danger">OverDue</span>';
                    }
                })

                ->setRowAttr([
                    'data-href' => function ($row) {
                        return  action('SaleBulkReturnController@show', [$row->id]);
                    }
                ])
                ->rawColumns(['total_return', 'total_refund', 'payment_due', 'action', 'payment_status'])
                ->make(true);
        }

        $super_admin = SuperAdmin::first();
        $customers = Contact::customersDropdown($business_id, false);

        return view('sell_bulk_return.index')->with(compact('super_admin', 'customers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

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
        $customer = Contact::where('id', $stock_adjustment->contact_id)->first();

        return view('sell_bulk_return.show')
            ->with(compact('stock_adjustment', 'customer', 'lot_n_exp_enabled', 'activities', 'super_admin'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

        /**
     * Returns the content for the receipt
     *
     * @param  int  $business_id
     * @param  int  $location_id
     * @param  int  $transaction_id
     * @param string $printer_type = null
     *
     * @return array
     */
    private function receiptContent(
        $business_id,
        $location_id,
        $transaction_id,
        $printer_type = null
    ) {
        $output = ['is_enabled' => false,
                    'print_type' => 'browser',
                    'html_content' => null,
                    'printer_config' => [],
                    'data' => []
                ];

        $business_details = $this->businessUtil->getDetails($business_id);
        $location_details = BusinessLocation::find($location_id);

        //Check if printing of invoice is enabled or not.
        if ($location_details->print_receipt_on_invoice == 1) {
            //If enabled, get print type.
            $output['is_enabled'] = true;

            $invoice_layout = $this->businessUtil->invoiceLayout($business_id, $location_details->invoice_layout_id);

            //Check if printer setting is provided.
            $receipt_printer_type = is_null($printer_type) ? $location_details->receipt_printer_type : $printer_type;

            $receipt_details = $this->transactionUtil->getReceiptDetails($transaction_id, $location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type);
            
            //If print type browser - return the content, printer - return printer config data, and invoice format config
            $output['print_title'] = $receipt_details->invoice_no;
            if ($receipt_printer_type == 'printer') {
                $output['print_type'] = 'printer';
                $output['printer_config'] = $this->businessUtil->printerConfig($business_id, $location_details->printer_id);
                $output['data'] = $receipt_details;
                
            } else {
                $output['html_content'] = view('sell_return.receipt', compact('receipt_details'))->render();
            }
        }

        return $output;
    }

    public function addPayment($transaction_id)
    {
        if (!auth()->user()->can('purchase.payments') && !auth()->user()->can('sell.payments') && !auth()->user()->can('all_expense.access') && !auth()->user()->can('view_own_expense')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $transaction = Transaction::where('business_id', $business_id)
                                        ->with(['contact', 'location'])
                                        ->findOrFail($transaction_id);
            if ($transaction->payment_status != 'paid') {
                $show_advance = in_array($transaction->type, ['sell', 'purchase']) ? true : false;
                $payment_types = $this->transactionUtil->payment_types($transaction->location, $show_advance);

                $paid_amount = $this->transactionUtil->getTotalPaid($transaction_id);
                $amount = $transaction->bulk_return_payment_amount - $paid_amount;
                if ($amount < 0) {
                    $amount = 0;
                }

                $amount_formated = $this->transactionUtil->num_f($amount);

                $payment_line = new TransactionPayment();
                $payment_line->amount = $amount;
                $payment_line->method = 'cash';
                $payment_line->paid_on = \Carbon::now()->toDateTimeString();

                //Accounts
                $accounts = $this->moduleUtil->accountsDropdown($business_id, true, false, true);

                $view = view('sell_bulk_return.partials.payment_row')
                ->with(compact('transaction', 'payment_types', 'payment_line', 'amount_formated', 'accounts'))->render();

                $output = [ 'status' => 'due',
                                    'view' => $view];
            } else {
                $output = [ 'status' => 'paid',
                                'view' => '',
                                'msg' => __('purchase.amount_already_paid')  ];
            }

            return json_encode($output);
        }

        
    }

    public function createPayment(Request $request)
    {
        try {
            $business_id = $request->session()->get('user.business_id');
            $transaction_id = $request->input('transaction_id');
            $transaction = Transaction::where('business_id', $business_id)->with(['contact'])->findOrFail($transaction_id);

            $transaction_before = $transaction->replicate();

            if (!(auth()->user()->can('purchase.payments') || auth()->user()->can('sell.payments') || auth()->user()->can('all_expense.access') || auth()->user()->can('view_own_expense'))) {
                abort(403, 'Unauthorized action.');
            }

            if ($transaction->payment_status != 'paid') {
                $inputs = $request->only(['amount', 'method', 'note', 'card_number', 'card_holder_name',
                'card_transaction_number', 'card_type', 'card_month', 'card_year', 'card_security',
                'cheque_number', 'bank_account_number']);

                $cheque_input = $request->only(['cheque_issued_date', 'cheque_type']);

                $inputs['paid_on'] = $this->transactionUtil->uf_date($request->input('paid_on'), true);
                $inputs['transaction_id'] = $transaction->id;
                $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);
                $inputs['created_by'] = auth()->user()->id;
                $inputs['payment_for'] = $transaction->contact_id;

                if ($inputs['method'] == 'custom_pay_1') {
                    $inputs['transaction_no'] = $request->input('transaction_no_1');
                } elseif ($inputs['method'] == 'custom_pay_2') {
                    $inputs['transaction_no'] = $request->input('transaction_no_2');
                } elseif ($inputs['method'] == 'custom_pay_3') {
                    $inputs['transaction_no'] = $request->input('transaction_no_3');
                }

                if (!empty($request->input('account_id')) && $inputs['method'] != 'advance') {
                    $inputs['account_id'] = $request->input('account_id');
                }

                $prefix_type = 'purchase_payment';
                if (in_array($transaction->type, ['sell', 'sell_return'])) {
                    $prefix_type = 'sell_payment';
                } elseif (in_array($transaction->type, ['expense', 'expense_refund'])) {
                    $prefix_type = 'expense_payment';
                }

                $cheque_amount = 0.0;
                if ($inputs['method'] == 'cheque') {
                    $cheque_amount = $inputs['amount'];
                    $inputs['amount'] = 0.0;
                }

                DB::beginTransaction();

                $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);
                //Generate reference number
                $inputs['payment_ref_no'] = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

                $inputs['business_id'] = $request->session()->get('business.id');
                $inputs['document'] = $this->transactionUtil->uploadFile($request, 'document', 'documents');

                //Pay from advance balance
                $payment_amount = $inputs['amount'];
                $contact_balance = !empty($transaction->contact) ? $transaction->contact->balance : 0;
                if ($inputs['method'] == 'advance' && $inputs['amount'] > $contact_balance) {
                    throw new AdvanceBalanceNotAvailable(__('lang_v1.required_advance_balance_not_available'));
                }

                if ($inputs['method'] != 'cheque') {
                    if (!empty($inputs['amount'])) {
                        $tp = TransactionPayment::create($inputs);
                        $inputs['transaction_type'] = 'sell_return';
                        event(new TransactionPaymentAdded($tp, $inputs));
                    }
                } else {
                    // if (!empty($inputs['amount'])) {
                        $tp = TransactionPayment::create($inputs);
                        $inputs['transaction_type'] = 'sell_return';
                        event(new TransactionPaymentAdded($tp, $inputs));
                    // }
                }
                
                // if (!empty($inputs['amount'])) {
                //     $tp = TransactionPayment::create($inputs);

                //     if (!empty($request->input('denominations'))) {
                //         $this->transactionUtil->addCashDenominations($tp, $request->input('denominations'));
                //     }

                //     $inputs['transaction_type'] = $transaction->type;
                //     event(new TransactionPaymentAdded($tp, $inputs));
                // }

                if ($inputs['method'] == 'cheque') {
                    $cheque_inputs['cheque_number'] = $inputs['cheque_number'];
                    $cheque_inputs['cheque_issued_date'] = $inputs['paid_on'];
                    $cheque_inputs['cheque_date'] = $cheque_input['cheque_issued_date'];
                    $cheque_inputs['cheque_amount'] = $cheque_amount;
                    $cheque_inputs['cheque_status'] = 'due';
                    $cheque_inputs['cheque_type'] = $cheque_input['cheque_type'];
                    $cheque_inputs['account_id'] = $inputs['account_id'];
                    $cheque = Cheque::create($cheque_inputs);

                    ChequeTransaction::create([
                        'cheque_amount' => $cheque_amount,
                        'cheque_id' => $cheque->id,
                        'transaction_id' => $transaction_id,
                        'contact_id' => $transaction->contact_id
                    ]);
                }

                //update payment status
                $payment_status = $this->transactionUtil->updatePaymentStatus($transaction_id, $transaction->bulk_return_payment_amount);
                $transaction->payment_status = $payment_status;

                $this->transactionUtil->activityLog($transaction, 'payment_edited', $transaction_before);
                
                DB::commit();
            }

            $output = ['success' => true,
                            'msg' => __('purchase.payment_added_success')
                        ];
        } catch (\Exception $e) {
            DB::rollBack();
            $msg = __('messages.something_went_wrong');

            if (get_class($e) == \App\Exceptions\AdvanceBalanceNotAvailable::class) {
                $msg = $e->getMessage();
            } else {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            }

            $output = ['success' => false,
                          'msg' => $msg
                      ];
        }

        return redirect()->back()->with(['status' => $output]);
    }
}
