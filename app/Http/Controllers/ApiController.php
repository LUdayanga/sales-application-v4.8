<?php

namespace App\Http\Controllers;

use App\Transaction;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function testAPI(Request $request)
    {
        $transactions = Transaction::where('type', 'sell')->get();
        return $transactions;
    }

    public function postData(Request $request){
        $get_arrs = $request->array;
        foreach ($get_arrs as $rslt) {
            return $rslt;
        }
    }
}
