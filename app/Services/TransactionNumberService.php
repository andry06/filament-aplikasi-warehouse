<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Transaction;

class TransactionNumberService
{
    public static function generateGoodReceiveNumber()
    {
        $date = Carbon::parse(today());
        $transaction = Transaction::whereYear('created_at', $date->year)
            ->whereMonth('created_at', $date->month)
            ->orderBy('counter', 'desc')
            ->first();
        info($transaction);
        $counterBefore = $transaction ? $transaction->counter : 0;
        $prefix = 'PB-';
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
}
