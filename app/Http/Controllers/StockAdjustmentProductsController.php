<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Transaction;
use DB;
use Datatables;

class StockAdjustmentProductsController extends Controller
{
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

            $stock_adjustments = Transaction::where('transactions.business_id', $business_id)
                ->join('stock_adjustment_lines', 'transactions.id', '=', 'stock_adjustment_lines.transaction_id')
                ->join('products', 'products.id', '=', 'stock_adjustment_lines.product_id')
                ->where('transactions.type', 'stock_adjustment');

            $start_date = request()->get('start_date');
            $end_date = request()->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $stock_adjustments->whereBetween(DB::raw('date(transactions.transaction_date)'), [$start_date, $end_date]);
            }

            
            if (!empty($location_id)) {
                $stock_adjustments->where('transactions.location_id', $location_id);
            }

            $products = $stock_adjustments->select(
                'stock_adjustment_lines.product_id as product_id',
                'products.name as name',
                'products.sku as sku',

            )->groupBy('stock_adjustment_lines.product_id');

            return Datatables::of($products)

                ->addColumn('name', function ($row) {
                    return $row->name;
                })
                ->addColumn('sku', function ($row) {
                    return $row->sku;
                })
                ->addColumn('total_decrease', function ($row) {
                    $total_decrease = 0;
                    $location_id = request()->get('location_id');
                    if(!empty($location_id)){
                        $total_decrease = Transaction::leftJoin('stock_adjustment_lines', 'transactions.id', '=', 'stock_adjustment_lines.transaction_id')
                        ->where('stock_adjustment_lines.product_id', $row->product_id)
                        ->where('transactions.location_id', $location_id)
                        ->where('transactions.sub_type', 'decrease')
                        ->sum('transactions.final_total');
                    }else{
                        $total_decrease = Transaction::leftJoin('stock_adjustment_lines', 'transactions.id', '=', 'stock_adjustment_lines.transaction_id')
                        ->where('stock_adjustment_lines.product_id', $row->product_id)
                        ->where('transactions.sub_type', 'decrease')
                        ->sum('transactions.final_total');
                    }
                    
                    return 'Rs ' . number_format($total_decrease, 2);    
                })

                ->addColumn('total_increase', function ($row) {
                    $total_increase = 0;
                    $location_id = request()->get('location_id');
                    if(!empty($location_id)){
                        $total_increase = Transaction::leftJoin('stock_adjustment_lines', 'transactions.id', '=', 'stock_adjustment_lines.transaction_id')
                        ->where('stock_adjustment_lines.product_id', $row->product_id)
                        ->where('transactions.location_id', $location_id)
                        ->where('transactions.sub_type', 'increase')
                        ->sum('transactions.final_total');
                    }else{
                        $total_increase = Transaction::leftJoin('stock_adjustment_lines', 'transactions.id', '=', 'stock_adjustment_lines.transaction_id')
                        ->where('stock_adjustment_lines.product_id', $row->product_id)
                        ->where('transactions.sub_type', 'increase')
                        ->sum('transactions.final_total');
                    }
                    

                    return 'Rs ' . number_format($total_increase, 2);      
                })
                ->addColumn('amount', function ($row) {
                    $location_id = request()->get('location_id');
                    $decrease = 0;
                    if(!empty($location_id)){
                        $decrease = Transaction::leftJoin('stock_adjustment_lines', 'transactions.id', '=', 'stock_adjustment_lines.transaction_id')
                        ->where('stock_adjustment_lines.product_id', $row->product_id)
                        ->where('transactions.location_id', $location_id)
                        ->where('transactions.sub_type', 'decrease')
                        ->sum('transactions.final_total');
                    }else{
                        $decrease = Transaction::leftJoin('stock_adjustment_lines', 'transactions.id', '=', 'stock_adjustment_lines.transaction_id')
                        ->where('stock_adjustment_lines.product_id', $row->product_id)
                        ->where('transactions.sub_type', 'decrease')
                        ->sum('transactions.final_total');
                    }

                    $increase = 0;
                    if(!empty($location_id)){
                        $increase = Transaction::leftJoin('stock_adjustment_lines', 'transactions.id', '=', 'stock_adjustment_lines.transaction_id')
                        ->where('stock_adjustment_lines.product_id', $row->product_id)
                        ->where('transactions.location_id', $location_id)
                        ->where('transactions.sub_type', 'increase')
                        ->sum('transactions.final_total');
                    }else{
                        $increase = Transaction::leftJoin('stock_adjustment_lines', 'transactions.id', '=', 'stock_adjustment_lines.transaction_id')
                        ->where('stock_adjustment_lines.product_id', $row->product_id)
                        ->where('transactions.sub_type', 'increase')
                        ->sum('transactions.final_total');
                    }
                    
                    $amount = $increase - $decrease;
                    return 'Rs ' . number_format($amount, 2);      
                })

                ->rawColumns(['name', 'sku', 'total_decrease', 'total_increase', 'amount'])
                ->make(true);
        }

        return view('stock_adjustment.index');
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
        //
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
}
