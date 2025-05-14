<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Stock;
use App\Models\Transaction;
use App\Models\StockHistory;
use App\Models\TransactionDetail;

class StockService
{
    public function getStockForUpdate(int $itemVariantId, int $warehouseId): float
    {
        $itemStock = Stock::where('warehouse_id', $warehouseId)
                        ->where('item_variant_id', $itemVariantId)
                        ->lockForUpdate()
                        ->first();

        return $itemStock ? $itemStock->stock : 0;
    }

    public function getStock(int $itemVariantId, int $warehouseId): float
    {
        $itemStock = Stock::where('warehouse_id', $warehouseId)
                        ->where('item_variant_id', $itemVariantId)
                        ->first();

        return $itemStock ? $itemStock->stock : 0;
    }


    public function updateStockItem(Transaction $transaction, TransactionDetail $transactionDetail, float $beginStock, string $movementType):void
    {
        $endingStock = ($movementType == 'in') ? ($beginStock + $transactionDetail->qty) : ($beginStock - $transactionDetail->qty);
        Stock::updateOrCreate(
            [
                'item_variant_id' => $transactionDetail->item_variant_id,
                'warehouse_id' => $transaction->warehouse_id
            ],
            [
                'stock' => $endingStock
            ]
        );

    }

    public function addStockHistoryItem(Transaction $transaction, TransactionDetail $transactionDetail, float $beginStock):void
    {
        $lastStockHistory = StockHistory::where('warehouse_id', $transaction->warehouse_id)
            ->where('item_variant_id', $transactionDetail->item_variant_id)
            ->orderBy('date', 'desc')->orderBy('id', 'desc')->first();

        $movementType = in_array($transaction->type, ['purchase_in', 'production_return']) ? 'in' : 'out';
        StockHistory::create([
            'warehouse_id' => $transaction->warehouse_id,
            'transaction_detail_id' => $transactionDetail->id,
            'item_variant_id' => $transactionDetail->item_variant_id,
            'date' => $transaction->date,
            'begin_stock' => $beginStock,
            'qty' => $transactionDetail->qty,
            'ending_stock' => ($movementType == 'in') ? ($beginStock + $transactionDetail->qty) : ($beginStock - $transactionDetail->qty),
            'movement_type' => $movementType
        ]);

        if ($lastStockHistory) {
            if (! $this->isUptudateHistoryStock($transaction, $lastStockHistory)) {
                $this->refreshBeginEndingStock($transaction, $transactionDetail->item_variant_id);
            }
        }

    }

    public function cancelStockHistoryItem(Transaction $transaction)
    {
        $transactionDetails = $transaction->transactionDetails()->get();
        $movementTypeCancel = in_array($transaction->type, ['purchase_in', 'production_return']) ? 'out' : 'in';

        foreach ($transactionDetails as $transactionDetail) {
            $stockHistory = $transactionDetail->stockHistory;

            $stockHistoryId = $stockHistory->id;

            $stockHistory->delete();

            $beginStock = $this->getStockForUpdate($transactionDetail->item_variant_id, $transaction->warehouse_id);

            $this->updateStockItem($transaction, $transactionDetail, $beginStock, $movementTypeCancel);

            $lastStockHistory = StockHistory::where('warehouse_id', $transaction->warehouse_id)
                ->where('item_variant_id', $transactionDetail->item_variant_id)
                ->orderBy('date', 'desc')->orderBy('id', 'desc')->first();

            if ($lastStockHistory){
                if ($stockHistoryId < $lastStockHistory->id) {
                    $this->refreshBeginEndingStock($transaction, $transactionDetail->item_variant_id);
                }
            }

        }
    }

    public function isUptudateHistoryStock(Transaction $transaction, StockHistory $lastStockHistory): bool
    {
        $lastDateTransaction = Carbon::parse($lastStockHistory->date);
        $transactionDate = Carbon::parse($transaction->date);

        return $lastDateTransaction->gte($transactionDate);
    }


    public function refreshBeginEndingStock(Transaction $transaction, int $itemVariantId): void
    {
        $beforeStockHistory = StockHistory::where('warehouse_id', $transaction->warehouse_id)
            ->where('item_variant_id', $itemVariantId)
            ->whereDate('date', '<', $transaction->date->format('Y-m-d'))
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $stockHistories = StockHistory::where('warehouse_id', $transaction->warehouse_id)
            ->where('item_variant_id', $itemVariantId)
            ->whereDate('date', '>=', $transaction->date->format('Y-m-d'))
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $beginStock = $beforeStockHistory ? $beforeStockHistory->stock : 0;
        foreach ($stockHistories as $stockHistory) {
            $endingStock = $stockHistory->movement_type == 'in' ? ($beginStock + $stockHistory->qty) : ($beginStock - $stockHistory->qty);
            $stockHistory->update([
                'begin_stock' => $beginStock,
                'ending_stock' => $endingStock,
            ]);

            $beginStock = $endingStock;
        }
    }

}
