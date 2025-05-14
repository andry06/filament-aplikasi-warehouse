<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Http\Request;

class PrintController extends Controller
{
    public function printGoodReceive(Transaction $transaction)
    {
        if($transaction->type != 'purchase_in') {
            abort(404);
        }

        return view('prints.good-receive', [
            'transaction' => $transaction,
            'transactionDetails' => $transaction->transactionDetails()->get(),
            'setting' => Setting::first()
        ]);
    }

    public function printPurchaseReturn(Transaction $transaction)
    {
        if($transaction->type != 'purchase_return') {
            abort(404);
        }

        return view('prints.purchase-return', [
            'transaction' => $transaction,
            'transactionDetails' => $transaction->transactionDetails()->get(),
            'setting' => Setting::first()
        ]);
    }
}
