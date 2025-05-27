<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Project;
use App\Models\ItemVariant;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Database\Eloquent\Model;

class ProductionReturnService
{
    public function approve(Transaction $transaction): void
    {
        if ($transaction->status != 'draft') {
            throw new \Exception('Status bukan "draft", tidak bisa diapprove.');
        }

        $transactionDetails = $transaction->transactionDetails()->get();

        $stockService = app(StockService::class);

        foreach ($transactionDetails as $transactionDetail) {
            $beginStock = $stockService->getStockForUpdate($transactionDetail->item_variant_id, $transaction->warehouse_id);

            $stockService->updateStockItem($transaction, $transactionDetail, $beginStock, 'plus');
            $stockService->updateStockMutationForApprove($transaction, $transactionDetail);
        }

        $transaction->update(['status' => 'approve']);

        $this->updateProject($transaction->project_id);
    }

    public function cancelApprove(Transaction $transaction): void
    {
        if ($transaction->status != 'approve') {
            throw new \Exception('Status bukan "approve", tidak bisa dibatalkan.');
        }

        $stockService = app(StockService::class);
        $transactionDetails = $transaction->transactionDetails()->get();

        foreach ($transactionDetails as $transactionDetail) {
            $beginStock = $stockService->getStockForUpdate($transactionDetail->item_variant_id, $transaction->warehouse_id);
            $stockService->updateStockItem($transaction, $transactionDetail, $beginStock, 'minus');
            $stockService->updateStockMutationForCancelApprove($transaction, $transactionDetail);
        }

        $transaction->update(['status' => 'draft']);

        $this->updateProject($transaction->project_id);
    }

    public function addTransactionDetail(Model $transaction, array $data): void
    {
        $transactionIds = Transaction::where('project_id', $transaction->project_id)
                                ->where('status', 'approve')
                                ->where('type', 'production_allocation')
                                ->pluck('id')->toArray();

        $transactionDetail = TransactionDetail::whereIn('transaction_id', $transactionIds)
            ->where('item_variant_id', $data['item_variant_id'])
            ->first();

        $transaction->transactionDetails()
            ->create($data + [
                'price' => $transactionDetail->price,
                'type' => 'in'
            ]);
    }

    public function updateProject(int $projectId): void
    {
        $project = Project::find($projectId);

        $project->update([
            'material_cost' => $project->total_material_cost
        ]);
    }
}


