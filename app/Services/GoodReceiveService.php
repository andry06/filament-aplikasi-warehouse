<?php

namespace App\Services;

use App\Models\GoodReceive;
use App\Exceptions\InvalidStatusChangeException;
use App\Models\Transaction;
use Exception;

class GoodReceiveService
{
    public function cancelApprove(Transaction $transaction): void
    {
        if ($transaction->status != 'approve') {
            throw new Exception('Status bukan "approve", tidak bisa dibatalkan.');
        }

        $transaction->update(['status' => 'draft']);
    }

    public function approve(Transaction $transaction): void
    {
        if ($transaction->status != 'draft') {
            throw new Exception('Status bukan "draft", tidak bisa diapprove.');
        }

        $transaction->update(['status' => 'approve']);
    }
}
