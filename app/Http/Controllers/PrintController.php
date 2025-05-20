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

    public function printProductionAllocation(Transaction $transaction)
    {
        if($transaction->type != 'production_allocation') {
            abort(404);
        }

        return view('prints.production-allocation', [
            'transaction' => $transaction,
            'transactionDetails' => $transaction->transactionDetails()->get(),
            'setting' => Setting::first()
        ]);
    }

    public function printProductionReturn(Transaction $transaction)
    {
        if($transaction->type != 'production_return') {
            abort(404);
        }

        return view('prints.production-return', [
            'transaction' => $transaction,
            'transactionDetails' => $transaction->transactionDetails()->get(),
            'setting' => Setting::first()
        ]);
    }

    public function printStockOpname(Transaction $transaction)
    {
        if($transaction->type != 'stock_opname') {
            abort(404);
        }

        $transactionDetails = $transaction->transactionDetails()
            ->select('items.category','transaction_details.*', 'items.code as item_code', 'items.name as item_name', 'item_variants.color as item_color')
            ->join('item_variants', 'item_variants.id', '=', 'transaction_details.item_variant_id')
            ->join('items', 'items.id', '=', 'item_variants.item_id')
            ->get();

        return view('prints.stock-opname', [
            'transaction' => $transaction,
            'transactionDetails' => $transactionDetails,
            'setting' => Setting::first()
        ]);
    }
}
