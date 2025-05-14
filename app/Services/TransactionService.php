<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Transaction;
use App\Models\TransactionDetail;

class TransactionService
{
    public static function generateGoodReceiveNumber()
    {
        $date = Carbon::parse(today());
        $transaction = Transaction::whereYear('created_at', $date->year)
            ->whereMonth('created_at', $date->month)
            ->where('type', 'purchase_in')
            ->orderBy('counter', 'desc')
            ->first();

        $counterBefore = $transaction ? $transaction->counter : 0;
        $prefix = 'BBM-'; // pembelian barang masuk
        $counter = $counterBefore+1; // Atau menggunakan nomor urut lainnya
        // Array bulan Romawi
        $romanMonths = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];

        return [
            'counter' => $counter,
            'number' => "{$prefix}" . str_pad($counter, 3, '0', STR_PAD_LEFT) . "/CRJ/{$romanMonths[$date->month]}/{$date->year}",
        ];
    }

    public static function generatePurchaseReturnNumber()
    {
        $date = Carbon::parse(today());
        $transaction = Transaction::whereYear('created_at', $date->year)
            ->whereMonth('created_at', $date->month)
            ->where('type', 'purchase_return')
            ->orderBy('counter', 'desc')
            ->first();

        $counterBefore = $transaction ? $transaction->counter : 0;
        $prefix = 'BBK-'; // Pengembalian Barang Supplier
        $counter = $counterBefore+1; // Atau menggunakan nomor urut lainnya
        // Array bulan Romawi
        $romanMonths = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];

        return [
            'counter' => $counter,
            'number' => "{$prefix}" . str_pad($counter, 3, '0', STR_PAD_LEFT) . "/CRJ/{$romanMonths[$date->month]}/{$date->year}",
        ];
    }

    public function getAveragePriceItemOtgoing(int $itemVariantId,int $warehouseId, float $qtyRequest)
    {
        $transactionDetails = TransactionDetail::join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->where('transaction_details.qty_remaining', '>', 0)
            ->where('transaction_details.item_variant_id', $itemVariantId)
            ->where('transactions.warehouse_id', $warehouseId)
            ->where('transactions.type', 'purchase_in')
            ->where('transactions.status', 'approve')
            ->orderBy('transactions.date')
            ->orderBy('transaction_details.id')
            ->get();

        $qtyUsed = $qtyRequest;
        $totalPrice = 0;

        foreach ($transactionDetails as $transactionDetail) {
            if($qtyRequest > 0){
                if ($qtyRequest > $transactionDetail->qty_remaining) {
                    $totalPrice += $transactionDetail->price * $transactionDetail->qty_remaining;
                } else {
                    $totalPrice += $transactionDetail->price * $qtyRequest;
                }

                $qtyRequest -= $transactionDetail->qty_remaining;
            } else {
                break;
            }
        }

        return round($totalPrice / $qtyUsed);

    }
}
