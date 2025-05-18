<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Stock;
use App\Models\Transaction;
use App\Models\StockHistory;
use App\Models\StockMutation;
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

    public function getStockMutationForUpdate(int $warehouseId, Carbon $date, int $itemVariantId): ?StockMutation
    {
        return StockMutation::where('date', $date->format('Y-m-d'))
            ->where('item_variant_id', $itemVariantId)
            ->where('warehouse_id', $warehouseId)
            ->first();
    }


    public function updateStockItem(Transaction $transaction, TransactionDetail $transactionDetail, float $beginStock, string $operation):void
    {
        $endingStock = ($operation == 'plus') ? ($beginStock + $transactionDetail->qty) : ($beginStock - $transactionDetail->qty);
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

    public function updateStockMutationForApprove(Transaction $transaction, TransactionDetail $transactionDetail):void
    {
        $stockMutationToDate = $this->getStockMutationForUpdate($transaction->warehouse_id, $transaction->date, $transactionDetail->item_variant_id);

        if ($stockMutationToDate) {
            if ($transactionDetail->type == 'in') {
                $stockMutationToDate->update([
                    'qty_in' => $stockMutationToDate->qty_in + $transactionDetail->qty,
                ]);
            } else {
                $stockMutationToDate->update([
                    'qty_out' => $stockMutationToDate->qty_out + $transactionDetail->qty
                ]);
            }
        }else{
            StockMutation::create([
                'warehouse_id' => $transaction->warehouse_id,
                'item_variant_id' => $transactionDetail->item_variant_id,
                'date' => $transaction->date,
                'qty_in' => ($transactionDetail->type == 'in') ? $transactionDetail->qty : 0,
                'qty_out' => ($transactionDetail->type == 'out') ? $transactionDetail->qty : 0
            ]);
        }

        $this->refreshBeginEndingStockMutation($transaction, $transactionDetail->item_variant_id);
    }

    public function updateStockMutationForCancelApprove(Transaction $transaction, TransactionDetail $transactionDetail):void
    {
        $stockMutationToDate = $this->getStockMutationForUpdate($transaction->warehouse_id, $transaction->date, $transactionDetail->item_variant_id);

        if ($transactionDetail->type == 'in') {
            $stockMutationToDate->update([
                'qty_in' => $stockMutationToDate->qty_in - $transactionDetail->qty,
            ]);
        } else {
            $stockMutationToDate->update([
                'qty_out' => $stockMutationToDate->qty_out - $transactionDetail->qty
            ]);
        }

        if ($stockMutationToDate->qty_in == 0 && $stockMutationToDate->qty_out == 0) {
            $stockMutationToDate->delete();
        }

        $this->refreshBeginEndingStockMutation($transaction, $transactionDetail->item_variant_id);
    }


    public function refreshBeginEndingStockMutation(Transaction $transaction, int $itemVariantId): void
    {
        $beforeStockMutation = StockMutation::where('warehouse_id', $transaction->warehouse_id)
            ->where('item_variant_id', $itemVariantId)
            ->whereDate('date', '<', $transaction->date->format('Y-m-d'))
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $beginStock = $beforeStockMutation ? $beforeStockMutation->ending_stock : 0;

        $stockMutations = StockMutation::where('warehouse_id', $transaction->warehouse_id)
            ->where('item_variant_id', $itemVariantId)
            ->whereDate('date', '>=', $transaction->date->format('Y-m-d'))
            ->orderBy('date', 'asc')
            ->get();

        foreach ($stockMutations as $stockMutation) {
            $endingStock = $beginStock + $stockMutation->qty_in - $stockMutation->qty_out;
            $stockMutation->update([
                'begin_stock' => $beginStock,
                'ending_stock' => $endingStock,
            ]);
            $beginStock = $endingStock;
        }
    }

    public function getProductionStockOnProject(int $projectId, int $itemVariantId): float
    {
        $totalQtyItemAllocation = $this->getTotalQtyItemTransactionOnProject($projectId, $itemVariantId, 'production_allocation');
        $totalQtyItemReturn = $this->getTotalQtyItemTransactionOnProject($projectId, $itemVariantId, 'production_return');
        return $totalQtyItemAllocation - $totalQtyItemReturn;
    }

    public function getTotalQtyItemTransactionOnProject(int $projectId, int $itemVariantId, string $type): float
    {
        $transactionIds = Transaction::where('project_id', $projectId)
            ->where('status', 'approve')
            ->where('type', $type)
            ->pluck('id')->toArray();

        return TransactionDetail::whereIn('transaction_id', $transactionIds)
            ->where('item_variant_id', $itemVariantId)
            ->sum('qty');
    }

}
