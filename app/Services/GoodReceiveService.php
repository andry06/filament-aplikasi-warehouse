<?php

namespace App\Services;

use App\Models\GoodReceive;
use App\Exceptions\InvalidStatusChangeException;
use App\Models\Transaction;
use Exception;

class GoodReceiveService
{
    public function approve(Transaction $transaction): void
    {
        if ($transaction->status != 'draft') {
            throw new Exception('Status bukan "draft", tidak bisa diapprove.');
        }

        $transactionDetails = $transaction->transactionDetails()->get();

        $stockService = app(StockService::class);

        foreach ($transactionDetails as $transactionDetail) {
            $beginStock = $stockService->getStockForUpdate($transactionDetail->item_variant_id, $transaction->warehouse_id);

            $stockService->updateStockItem($transaction, $transactionDetail, $beginStock, 'plus');
            $stockService->updateStockMutationForApprove($transaction, $transactionDetail);
        }

        $transaction->update(['status' => 'approve']);
    }

    public function cancelApprove(Transaction $transaction): void
    {
        if ($transaction->status != 'approve') {
            throw new Exception('Status bukan "approve", tidak bisa dibatalkan.');
        }

        $stockService = app(StockService::class);
        $transactionDetails = $transaction->transactionDetails()->get();

        foreach ($transactionDetails as $transactionDetail) {
            $beginStock = $stockService->getStockForUpdate($transactionDetail->item_variant_id, $transaction->warehouse_id);
            $stockService->updateStockItem($transaction, $transactionDetail, $beginStock, 'minus');
            $stockService->updateStockMutationForCancelApprove($transaction, $transactionDetail);
        }

        $transaction->update(['status' => 'draft']);

    }
}
