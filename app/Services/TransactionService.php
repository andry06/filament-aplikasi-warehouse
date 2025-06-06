<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Database\Eloquent\Model;

class TransactionService
{
    public static function generateGoodReceiveNumber(): array
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

    public static function generatePurchaseReturnNumber(): array
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

    public static function generateProductionAllocationNumber(): array
    {
        $date = Carbon::parse(today());
        $transaction = Transaction::whereYear('created_at', $date->year)
            ->whereMonth('created_at', $date->month)
            ->where('type', 'production_allocation')
            ->orderBy('counter', 'desc')
            ->first();

        $counterBefore = $transaction ? $transaction->counter : 0;
        $prefix = 'PAB-'; // Pengembalian Barang Supplier
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

    public static function generateProductionReturnNumber(): array
    {
        $date = Carbon::parse(today());
        $transaction = Transaction::whereYear('created_at', $date->year)
            ->whereMonth('created_at', $date->month)
            ->where('type', 'production_return')
            ->orderBy('counter', 'desc')
            ->first();

        $counterBefore = $transaction ? $transaction->counter : 0;
        $prefix = 'PPB-'; // Pengembalian Barang Supplier
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

    public static function generateStockOpnameNumber(): array
    {
        $date = Carbon::parse(today());
        $transaction = Transaction::whereYear('created_at', $date->year)
            ->whereMonth('created_at', $date->month)
            ->where('type', 'stock_opname')
            ->orderBy('counter', 'desc')
            ->first();

        $counterBefore = $transaction ? $transaction->counter : 0;
        $prefix = 'SO-'; // Pengembalian Barang Supplier
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

    public static function isNotAllowedApprove(Model $transaction): bool
    {
        $stockOpname = Transaction::where('type', 'stock_opname')
            ->where('warehouse_id', $transaction->warehouse_id)
            ->orderBy('date', 'desc')
            ->first();

        if (!$stockOpname) {
            return false;
        }

        $transactionTime = Carbon::parse($transaction->date);
        $stockOpnameTime = Carbon::parse($stockOpname->date);

        return $transactionTime->lt($stockOpnameTime);
    }
}
