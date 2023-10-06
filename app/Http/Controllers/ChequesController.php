<?php

namespace App\Http\Controllers;

use App\Account;
use App\AccountTransaction;
use App\Cheque;
use App\ChequeTransaction;
use App\Contact;
use App\Events\TransactionPaymentAdded;
use App\Transaction;
use App\TransactionPayment;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Log;

class ChequesController extends Controller
{
    /**
     * @var ModuleUtil
     */
    private $moduleUtil;
    private $transactionUtil;

    /**
     * ChequesController constructor.
     * @param ModuleUtil $moduleUtil
     * @param TransactionUtil $transactionUtil
     */
    public function __construct(ModuleUtil $moduleUtil, TransactionUtil $transactionUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        if (\request()->ajax()) {

            $cheques = Cheque::join('accounts', 'cheques.account_id', '=', 'accounts.id')
                ->leftJoin('cheque_transactions', 'cheque_transactions.cheque_id', '=', 'cheques.id')
                ->select(
                    'cheques.id',
                    'accounts.name',
                    //'accounts.cheque_return_fee',
                    'cheques.cheque_number',
                    'cheques.cheque_issued_date',
                    'cheques.cheque_date',
                    'cheques.cheque_amount as cheque_amount',
                    'cheques.cheque_status',
                    'cheques.cheque_type'
                )
                ->orderBy('cheques.cheque_date', 'desc')
                ->groupBy('cheques.id');
            //                ->get();

            //            filters
            $cheque_number = \request()->get('cheque_number');
            if ($cheque_number) {
                $cheques->where('cheque_number', $cheque_number);
            }
            $contact_id = \request()->get('contact_id');
            if ($contact_id) {
                $cheques->where('cheque_transactions.contact_id', $contact_id);
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $cheques->whereDate('cheque_date', '>=', $start)
                    ->whereDate('cheque_date', '<=', $end);
            }
            $account_id = \request()->get('account_id');
            if ($account_id) {
                $cheques->where('cheques.account_id', $account_id);
            }

            return datatables()->of($cheques)
                ->addColumn('action', function ($row) {
                    $html =
                        '<div class="btn-group"><button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">' . __("messages.actions") . '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button><ul class="dropdown-menu dropdown-menu-left" role="menu">';
                    $html .=
                        '<li><a href="' . action('ChequesController@show', [$row->id]) . '" class="view-cheque"><i class="fa fa-eye"></i> ' . __("messages.view") . '</a></li>';

                    // if ($row->cheque_status != 'paid') {
                    //     $html .=
                    //         '<li><a href="' . action('ChequesController@edit', [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
                    // }

                    if ($row->cheque_status == 'due') {
                        $html .=
                            '<li><a href="' . action('ChequesController@chequeMarkAsPaid', [$row->id]) . '" class="mark-as-paid"><i class="glyphicon glyphicon-edit"></i> ' . __("Mark As Paid") . '</a></li>';
                    } elseif ($row->cheque_status == 'paid') {
                        $html .=
                            '<li><a href="' . action('ChequesController@chequeMarkAsReturned', [$row->id]) . '" class="mark-as-returned"><i class="glyphicon glyphicon-edit"></i> ' . __("Mark As Returned") . '</a></li>';
                    }

                    if ($row->cheque_status != 'paid') {
                        $html .=
                            '<li><a href="' . action('ChequesController@destroy', [$row->id]) . '" class="delete-cheque"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                    }
                    $html .= '</ul></div>';

                    return $html;
                })
                ->addColumn('mass_delete', function ($row) {
                    return  '<input type="checkbox" class="row-select" value="' . $row->id . '">';
                })
                ->editColumn(
                    'cheque_amount',
                    '<span class="final-total" data-orig-value="{{$cheque_amount}}">@format_currency($cheque_amount)</span>'
                )
                ->addColumn('cheque_return_amount', function ($row) {
                    $total_cheque_return_amount =  $row->cheque_amount;
                    if ($row->cheque_status != 'return') {
                        $total_cheque_return_amount = 0;
                    }
                    $total_cheque_return_amount = '<span class="payment_due" data-orig-value="' . $total_cheque_return_amount . '">' . $this->transactionUtil->num_f($total_cheque_return_amount, true) . '</span>';
                    return $total_cheque_return_amount;
                })
                ->editColumn('cheque_status', function ($row) {
                    if ($row->cheque_status == 'due') {
                        return '<span class="label label-warning">Due</span>';
                    } elseif ($row->cheque_status == 'partial') {
                        return '<span class="label label-info">Partial</span>';
                    } elseif ($row->cheque_status == 'paid') {
                        return '<span class="label label-success">Paid</span>';
                    } else {
                        return '<span class="label label-danger">Returned</span>';
                    }
                })
                ->editColumn('cheque_type', function ($row) {
                    if ($row->cheque_type == 'giving') {
                        return '<span class="label label-info">Issued</span>';
                    } else {
                        return '<span class="label label-primary">Received</span>';
                    }
                })
                //                ->editColumn('cheque_return_fee', function ($row) {
                //                    return number_format($row->cheque_return_fee, 2);
                //                })
                ->rawColumns(['mass_delete', 'cheque_amount', 'cheque_return_amount', 'action', 'cheque_status', 'cheque_type'])
                ->make(true);
        }

        $business_id = request()->session()->get('user.business_id');
        $contacts = Contact::contactDropdown($business_id, false, false);
        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }
        return view('cheque.index', compact('contacts', 'accounts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');
        $contacts = Contact::contactDropdown($business_id, false, false);
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }
        return view('cheque.create', compact('contacts', 'accounts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        try {
            $business_id = $request->session()->get('user.business_id');
            $contact_id = $request->input('contact_id');

            $selected_transactions = $request->input('transactions');

            $contact = Contact::where('business_id', $business_id)
                ->findOrFail($contact_id);

            $due_payment_type = $contact->type == 'supplier' ? 'purchase' : 'sell';

            //get payment type (creditor debit)
            // $payment_type = AccountTransaction::getAccountTransactionType($due_payment_type);

            $prefix_type = '';
            $payment_type = '';
            if ($contact->type == 'customer') {
                $prefix_type = 'sell_payment';
                $payment_type = 'credit';
            } else if ($contact->type == 'supplier') {
                $prefix_type = 'purchase_payment';
                $payment_type = 'debit';
            }

            $payments = $request->get('cheques');

            DB::beginTransaction();

            foreach ($payments as $payment) {
                $inputs['paid_on'] = Carbon::parse($payment['cheque_issued_date'])->format('Y-m-d h:m:s');
                $inputs['amount'] = $payment['cheque_amount'];
                $inputs['method'] = 'cheque';
                $inputs['cheque_number'] = $payment['cheque_number'];
                $inputs['account_id'] = $payment['account_id'];
                $inputs['created_by'] = auth()->user()->id;
                $inputs['payment_for'] = $contact_id;
                $inputs['business_id'] = $business_id;
                $inputs['is_advance'] = 1;

                $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type, $business_id);
                //Generate reference number
                $payment_ref_no = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count, $business_id);

                $inputs['payment_ref_no'] = $payment_ref_no;
                $inputs['account_id'] = $payment['account_id'];

                $parent_payment = TransactionPayment::create($inputs);

                $cheque = Cheque::create([
                    'cheque_number' => $payment['cheque_number'],
                    'cheque_date' => Carbon::parse($payment['cheque_date'])->format('Y-m-d h:m:s'),
                    'cheque_issued_date' => Carbon::parse($payment['cheque_issued_date'])->format('Y-m-d h:m:s'),
                    'cheque_amount' => $payment['cheque_amount'],
                    'cheque_status' => 'paid',
                    'cheque_type' => $payment['cheque_type'],
                    'account_id' => $payment['account_id'],
                ]);

                $inputs['cheque_id'] = $cheque->id;
                
                $this->transactionUtil->multiplePayCheque($parent_payment, $due_payment_type, $cheque->id, $selected_transactions);

                // insert account trasaction
                $multiple_payments = TransactionPayment::where('cheque_id', $cheque->id)->get();
                $account_transactions = [];

                foreach($multiple_payments as $multiple_payment){
                    $account_array = [
                        'account_id' => $payment['account_id'],
                        'type' => $payment_type,
                        'amount' => $multiple_payment->amount,
                        'operation_date' => Carbon::parse($payment['cheque_issued_date'])->format('Y-m-d h:m:s'),
                        'created_by' => auth()->user()->id,
                        'transaction_id' => $multiple_payment->transaction_id,
                        'transaction_payment_id' => $multiple_payment->id,
                        'cheque_id' => $cheque->id,
                    ];

                    $account_transactions[] = $account_array;
                }

                AccountTransaction::insert($account_transactions);

                //delete parent transaction
                DB::table('transaction_payments')->where('id', $parent_payment->id)->delete();
            }

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __("Cheque created success")
            ];
        } catch (Exception $e) {
            DB::rollBack();
            // dd($e);
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return redirect()->route('cheque.index')->with('status', $output);
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return Application|Factory|Response|View
     */
    public function show($id)
    {
        $cheque = Cheque::find($id);
        $cheque_transactions = ChequeTransaction::join('transactions', 'cheque_transactions.transaction_id', '=', 'transactions.id')
            ->where('cheque_transactions.cheque_id', $cheque->id)
            ->select(
                'transactions.id',
                'transactions.invoice_no',
                'transactions.ref_no',
                'transactions.final_total',
                'cheque_transactions.cheque_amount',
                'transactions.transaction_date',
                'transactions.payment_status'
            )
            ->orderBy('transactions.id', 'desc')
            ->get();
        return \view('cheque.show', compact('cheque', 'cheque_transactions'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param $id
     * @return Application|Factory|View|void
     */
    public function edit($id)
    {
        try {
            $cheque = Cheque::find($id);
            $contact_id = ChequeTransaction::where('cheque_id', $cheque->id)->first()->contact_id;
            $cheque_transactions = ChequeTransaction::join('transactions', 'cheque_transactions.transaction_id', '=', 'transactions.id')
                ->where('cheque_transactions.cheque_id', $cheque->id)
                ->select(
                    'transactions.id',
                    'transactions.invoice_no',
                    'transactions.ref_no',
                    'transactions.final_total',
                    'transactions.transaction_date',
                    'transactions.payment_status'
                )
                ->orderBy('transactions.id', 'desc')
                ->get();


            $cheque['contact_id'] = $contact_id;
            $business_id = request()->session()->get('user.business_id');
            $contacts = Contact::contactDropdown($business_id, false, false);
            $accounts = [];
            if ($this->moduleUtil->isModuleEnabled('account')) {
                $accounts = Account::forDropdown($business_id, true, false);
            }
            return \view('cheque.edit', compact('cheque', 'accounts', 'contacts', 'cheque_transactions'));
        } catch (Exception $e) {
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $inputs = $request->except(['_token', '_method', 'transactions', 'cheque-related-invoice-table_length', 'contact_id', 'q']);
            $cheque = Cheque::find($id);
            $inputs['cheque_date'] = Carbon::parse($inputs['cheque_date'])->format('Y-m-d h:m:s');
            $inputs['cheque_issued_date'] = Carbon::parse($inputs['cheque_issued_date'])->format('Y-m-d h:m:s');
            DB::beginTransaction();
            foreach ($cheque->cheque_transactions as $cheque_transaction) {
                $cheque_transaction->delete();
            }

            $cheque->update($inputs);
            $transactions = $request->get('transactions');
            foreach ($transactions as $transaction_id) {
                ChequeTransaction::create([
                    'cheque_amount' => $inputs['cheque_amount'],
                    'cheque_id' => $cheque->id,
                    'transaction_id' => $transaction_id,
                    'contact_id' => $request->get('contact_id')
                ]);
            }
            DB::commit();
            $output = [
                'success' => true,
                'msg' => __("Cheque updated success")
            ];
        } catch (Exception $e) {
            DB::rollBack();
            // dd($e);
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }
        return redirect()->route('cheque.index')->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return array
     */
    public function destroy($id)
    {
        try {
            $cheque = Cheque::find($id);
            DB::beginTransaction();
            foreach ($cheque->cheque_transactions as $cheque_transaction) {
                $cheque_transaction->delete();
            }
            $account_transactions = AccountTransaction::where('cheque_id', $cheque->id)->get();
            foreach ($account_transactions as $account_transaction) {
                $account_transaction->delete();
            }
            $cheque->delete();
            DB::commit();
            $output = [
                'success' => true,
                'msg' => __("Cheque delete success")
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    public function getInvoicesRelatedToContact()
    {
        if (\request()->ajax()) {


            $contact_id = \request()->get('contact_id');

            if ($contact_id != 'all') {
                $business_id = request()->session()->get('user.business_id');

                $contact_related_transactions = Transaction::join('contacts', 'transactions.contact_id', '=', 'contacts.id')
                    ->leftJoin('transaction_payments', 'transaction_payments.transaction_id', '=', 'transactions.id')
                    ->where([['transactions.contact_id', $contact_id], ['transactions.business_id', $business_id]])
                    ->whereIn('transactions.payment_status', ['due', 'partial'])
                    ->select(

                        'transactions.id',
                        'transactions.invoice_no',
                        'transactions.ref_no',
                        'transactions.final_total',
                        DB::raw('SUM(transaction_payments.amount) as paid_amount'),
                        'transactions.transaction_date',
                        'transactions.payment_status'
                    )
                    ->groupBy('transactions.id')
                    ->orderBy('transactions.id', 'asc');
                // ->get();

                $start_date = !empty(request()->input('start_date')) ? request()->input('start_date') : '';
                $end_date = !empty(request()->input('end_date')) ? request()->input('end_date') : '';


                $start_date = request()->input('start_date');
                $end_date = request()->input('end_date');

                if (!empty($start_date) && !empty($end_date)) {
                    $contact_related_transactions->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
                }


                return datatables()->of($contact_related_transactions)
                    ->editColumn('invoice_no', function ($row) {
                        $html = '<input id="" name="transactions[]" value="' . $row->id . '" type="text" hidden>
                                <input id="invoice_total" value="' . $row->final_total . '" type="text" hidden>';
                        if (!empty($row->invoice_no)) {
                            return $html . $row->invoice_no;
                        }
                        return $html . $row->ref_no;
                    })
                    ->editColumn('final_total', function ($row) {
                        //$html = '<input id="final_total" type="hidden"value="'. number_format($row->final_total, 2) .'">';
                        $available_balance = $row->final_total - $row->paid_amount;
                        return number_format($available_balance, 2);
                    })
                    ->editColumn('payment_status', function ($row) {
                        $payment_status = $row->payment_status;
                        if ($payment_status == 'due') {
                            return '<span class="label label-warning">Due</span>';
                        } elseif ($payment_status == 'partial') {
                            return '<span class="label label-info">Partial</span>';
                        } elseif ($payment_status == 'paid') {
                            return '<span class="label label-success">Paid</span>';
                        }
                        return '<span class="label label-danger">Returned</span>';
                    })
                    ->addColumn('action', function () {
                        return '<button type="button" class="btn btn-sm btn-danger remove_invoice"><i class="fa fa-trash"></i></button>';
                    })
                    ->rawColumns(['invoice_no', 'final_total', 'payment_status', 'action'])
                    ->make(true);
            }
        }
    }


    public function chequeMarkAsPaid($id)
    {
        try {

            $contact_id = '';

            $cheque = Cheque::find($id);
            DB::beginTransaction();
            foreach ($cheque->cheque_transactions as $cheque_transaction) {
                $transaction = Transaction::find($cheque_transaction->transaction_id);

                $cheque_amount = $cheque_transaction->cheque_amount;

                if ($cheque_transaction->transaction_payment_id != 0) {
                    TransactionPayment::where('id', $cheque_transaction->transaction_payment_id)
                        ->where('transaction_id', $transaction->id)
                        ->where('cheque_number', $cheque->cheque_number)
                        ->update([
                            'amount' => $cheque_amount,
                            'cheque_id' => $cheque_transaction->cheque_id,
                        ]);

                    AccountTransaction::where('transaction_id', $transaction->id)
                        ->where('transaction_payment_id', $cheque_transaction->transaction_payment_id)
                        ->update([
                            'amount' => $cheque_amount,
                            'cheque_id' => $cheque_transaction->cheque_id,
                        ]);
                } else {

                    TransactionPayment::where('transaction_id', $transaction->id)
                        ->where('cheque_number', $cheque->cheque_number)
                        ->update([
                            'amount' => $cheque_amount,
                            'cheque_id' => $cheque_transaction->cheque_id,
                        ]);

                    AccountTransaction::where('transaction_id', $transaction->id)
                        ->update([
                            'amount' => $cheque_amount,
                            'cheque_id' => $cheque_transaction->cheque_id,
                        ]);
                }

                $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

                $contact_id = $cheque_transaction->contact_id;
            }
            $cheque->cheque_status = 'paid';
            $cheque->update();


            //update contact advance balance
            if (!empty($cheque->excess_amount)) {
                $excess_amount = $cheque->excess_amount;
                $contact = Contact::findOrFail($contact_id);
                $contact->balance += $excess_amount;
                $contact->save();
            }

            DB::commit();
            $output = [
                'success' => true,
                'msg' => __('Mark as paid')
            ];
        } catch (Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    public function chequeMarkAsReturned($id)
    {
        try {

            $business_id = request()->session()->get('user.business_id');
            $cheque = Cheque::find($id);
            $contact_id = '';
            DB::beginTransaction();

            $transaction_payments = TransactionPayment::where('cheque_id', $cheque->id)->get();

            foreach ($transaction_payments as $transaction_payment) {

                $payment = TransactionPayment::findOrFail($transaction_payment->id);

                if (!empty($payment->transaction_id)) {
                    TransactionPayment::deletePayment($payment);

                    //account transaction delete
                    $account_transactions = AccountTransaction::where('cheque_id', $cheque->id)->get();

                    foreach ($account_transactions as $account_transaction) {
                        $account_transaction->delete();
                    }
                } else { //advance payment
                    $adjusted_payments = TransactionPayment::where(
                        'parent_id',
                        $payment->id
                    )
                        ->get();

                    $total_adjusted_amount = $adjusted_payments->sum('amount');

                    //Get customer advance share from payment and deduct from advance balance
                    $total_customer_advance = $payment->amount - $total_adjusted_amount;
                    if ($total_customer_advance > 0) {
                        $this->transactionUtil->updateContactBalance($payment->payment_for, $total_customer_advance, 'deduct');
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

            $cheque->cheque_status = 'return';
            $cheque->update();

            //only expense
            $location_id = ' ';
            $cheque_transaction = $cheque->cheque_transactions->first();
            $cheque_location = Transaction::find($cheque_transaction->transaction_id);
            if (!empty($cheque_location)) {
                $location_id = $cheque_location->location_id;
            }

            //add cheque return payment
            if ($cheque->cheque_type == 'giving') {
                $cheque_return_fee = Account::where('id', $cheque->account_id)->first()->cheque_return_fee;
                if ($cheque_return_fee) {
                    $account_transaction_inputs['type'] = 'debit';
                    $account_transaction_inputs['amount'] = $cheque_return_fee;
                    $account_transaction_inputs['operation_date'] = now();
                    $account_transaction_inputs['created_by'] = auth()->user()->id;
                    $account_transaction_inputs['cheque_id'] = $cheque->id;
                    $account_transaction_inputs['note'] = 'Cheque return fee for : ' . $cheque->cheque_number;
                    $account_transaction_inputs['account_id'] = $cheque->account_id;
                    AccountTransaction::create($account_transaction_inputs);

                    $user_id = Auth::id();
                    $transaction_data['business_id'] = $business_id;
                    $transaction_data['location_id'] = $location_id;
                    $transaction_data['created_by'] = $user_id;
                    $transaction_data['type'] = 'expense';
                    $transaction_data['status'] = 'final';
                    $transaction_data['payment_status'] = 'due';
                    $transaction_data['final_total'] = $cheque_return_fee;

                    $transaction_data['transaction_date'] = \Carbon::now();


                    $transaction_data['total_before_tax'] = $transaction_data['final_total'];


                    //Update reference count
                    $ref_count = $this->transactionUtil->setAndGetReferenceCount('expense', $business_id);
                    //Generate reference number

                    $transaction_data['ref_no'] = $this->transactionUtil->generateReferenceNumber('expense', $ref_count, $business_id);


                    $transaction = Transaction::create($transaction_data);
                }
            }

            // //update contact advance balance
            // if (!empty($cheque->excess_amount)) {
            //     $excess_amount = $cheque->excess_amount;
            //     $contact = Contact::findOrFail($contact_id);
            //     $contact->balance -= $excess_amount;
            //     $contact->save();
            // }

            DB::commit();
            $output = [
                'success' => true,
                'msg' => __('Mark as returned')
            ];
        } catch (Exception $exception) {
            DB::rollBack();
            dd($exception);
            \Log::emergency("File:" . $exception->getFile() . "Line:" . $exception->getLine() . "Message:" . $exception->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }


    public function getNewChequeRow(Request $request)
    {
        if ($request->ajax()) {
            $index = $request->get('index');
            $business_id = request()->session()->get('user.business_id');
            $accounts = [];
            if ($this->moduleUtil->isModuleEnabled('account')) {
                $accounts = Account::forDropdown($business_id, true, false);
            }
            return \view('cheque.cheque_row', compact('accounts', 'index'));
        }
    }

    public function uf_date($date, $time = false)
    {
        $date_format = session('business.date_format');
        $mysql_format = 'Y-m-d';
        if ($time) {
            if (session('business.time_format') == 12) {
                $date_format = $date_format . ' h:i A';
            } else {
                $date_format = $date_format . ' H:i';
            }
            $mysql_format = 'Y-m-d H:i:s';
        }

        return !empty($date_format) ? \Carbon::createFromFormat($date_format, $date)->format($mysql_format) : null;
    }

    public function massReturn(Request $request)
    {
        try {
            if (!empty($request->input('selected_return_rows'))) {
                $selected_rows = explode(',', $request->input('selected_return_rows'));

                $cheques = Cheque::whereIn('id', $selected_rows)->get();

                DB::beginTransaction();

                foreach ($cheques as $cheque) {
                    if ($cheque->cheque_status != 'paid') {
                        $output = [
                            'success' => 0,
                            'msg' => __("Cheque status should be 'paid'")
                        ];
                        return redirect()->back()->with(['status' => $output]);
                    }

                    $transaction_payments = TransactionPayment::where('cheque_id', $cheque->id)->get();

                    foreach ($transaction_payments as $transaction_payment) {

                        $payment = TransactionPayment::findOrFail($transaction_payment->id);

                        if (!empty($payment->transaction_id)) {
                            TransactionPayment::deletePayment($payment);

                            //account transaction delete
                            $account_transactions = AccountTransaction::where('cheque_id', $cheque->id)->get();

                            foreach ($account_transactions as $account_transaction) {
                                $account_transaction->delete();
                            }
                        } else { //advance payment
                            $adjusted_payments = TransactionPayment::where(
                                'parent_id',
                                $payment->id
                            )
                                ->get();

                            $total_adjusted_amount = $adjusted_payments->sum('amount');

                            //Get customer advance share from payment and deduct from advance balance
                            $total_customer_advance = $payment->amount - $total_adjusted_amount;
                            if ($total_customer_advance > 0) {
                                $this->transactionUtil->updateContactBalance($payment->payment_for, $total_customer_advance, 'deduct');
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

                    $cheque->cheque_status = 'return';
                    $cheque->update();

                    //only expense
                    $location_id = ' ';
                    $cheque_transaction = $cheque->cheque_transactions->first();
                    $cheque_location = Transaction::find($cheque_transaction->transaction_id);
                    if (!empty($cheque_location)) {
                        $location_id = $cheque_location->location_id;
                    }

                    //add cheque return payment
                    if ($cheque->cheque_type == 'giving') {
                        $cheque_return_fee = Account::where('id', $cheque->account_id)->first()->cheque_return_fee;
                        if ($cheque_return_fee) {
                            $account_transaction_inputs['type'] = 'debit';
                            $account_transaction_inputs['amount'] = $cheque_return_fee;
                            $account_transaction_inputs['operation_date'] = now();
                            $account_transaction_inputs['created_by'] = auth()->user()->id;
                            $account_transaction_inputs['cheque_id'] = $cheque->id;
                            $account_transaction_inputs['note'] = 'Cheque return fee for : ' . $cheque->cheque_number;
                            $account_transaction_inputs['account_id'] = $cheque->account_id;
                            AccountTransaction::create($account_transaction_inputs);

                            $user_id = Auth::id();
                            $transaction_data['business_id'] = $business_id;
                            $transaction_data['location_id'] = $location_id;
                            $transaction_data['created_by'] = $user_id;
                            $transaction_data['type'] = 'expense';
                            $transaction_data['status'] = 'final';
                            $transaction_data['payment_status'] = 'due';
                            $transaction_data['final_total'] = $cheque_return_fee;

                            $transaction_data['transaction_date'] = \Carbon::now();


                            $transaction_data['total_before_tax'] = $transaction_data['final_total'];


                            //Update reference count
                            $ref_count = $this->transactionUtil->setAndGetReferenceCount('expense', $business_id);
                            //Generate reference number

                            $transaction_data['ref_no'] = $this->transactionUtil->generateReferenceNumber('expense', $ref_count, $business_id);


                            $transaction = Transaction::create($transaction_data);
                        }
                    }
                }
            }

            DB::commit();
            $output = [
                'success' => 1,
                'msg' => __('successfully Return')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong")
            ];

            return redirect()->back()->with(['status' => $output]);
        }

        return redirect()->back()->with(['status' => $output]);
    }

    public function massDestroy(Request $request)
    {
        try {
            if (!empty($request->input('selected_deleted_rows'))) {
                $selected_rows = explode(',', $request->input('selected_deleted_rows'));

                $cheques = Cheque::whereIn('id', $selected_rows)->get();

                DB::beginTransaction();

                foreach ($cheques as $cheque) {
                    if ($cheque->cheque_status == 'paid') {
                        $output = [
                            'success' => 0,
                            'msg' => __("Cheque status can't be 'paid'")
                        ];
                        return redirect()->back()->with(['status' => $output]);
                    }

                    foreach ($cheque->cheque_transactions as $cheque_transaction) {
                        $cheque_transaction->delete();
                    }
                    $account_transactions = AccountTransaction::where('cheque_id', $cheque->id)->get();
                    foreach ($account_transactions as $account_transaction) {
                        $account_transaction->delete();
                    }
                    $cheque->delete();
                }
            }

            DB::commit();
            $output = [
                'success' => 1,
                'msg' => __('successfully Delete')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong")
            ];

            return redirect()->back()->with(['status' => $output]);
        }

        return redirect()->back()->with(['status' => $output]);
    }
}
