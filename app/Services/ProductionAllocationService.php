<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Project;
use App\Models\ItemVariant;
use App\Models\Transaction;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Model;

class ProductionAllocationService
{
    public function approve(Transaction $transaction): void
    {
        if ($transaction->status != 'draft') {
            throw new \Exception('Status bukan "draft", tidak bisa diapprove.');
        }

        $stockService = app(StockService::class);
        $transactionDetails = $transaction->transactionDetails()->get();

        foreach ($transactionDetails as $transactionDetail) {
            $beginStock = $stockService->getStockForUpdate($transactionDetail->item_variant_id, $transaction->warehouse_id);

            if($transactionDetail->qty > $beginStock) {
                $item = Item::find($transactionDetail->item_id);
                $itemVariant = ItemVariant::find($transactionDetail->item_variant_id);
                throw new \Exception("Stok barang $item->code - $itemVariant->color tidak mencukupi.");
            }

            $stockService->updateStockItem($transaction, $transactionDetail, $beginStock, 'minus');
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
            $stockService->updateStockItem($transaction, $transactionDetail, $beginStock, 'plus');
            $stockService->updateStockMutationForCancelApprove($transaction, $transactionDetail);
        }

        $transaction->update(['status' => 'draft']);

        $this->updateProject($transaction->project_id);
    }

    public function updateProject(int $projectId): void
    {
        $transactionFirstProject = Transaction::where('project_id', $projectId)
            ->where('status', 'approve')
            ->orderBy('date')
            ->first();

        $project = Project::find($projectId);

        $project->update([
            'date' => $transactionFirstProject ? $transactionFirstProject->date : null,
            'has_allocation' => $transactionFirstProject ? true : false,
            'material_cost' => $project->total_material_cost
        ]);

    }

    public function addTransactionDetail(Model $transaction, array $data): void
    {
        $transaction->transactionDetails()
            ->create($data + [
                'price' => ItemVariant::find($data['item_variant_id'])?->price,
                'type' => 'in'
            ]);
    }





}
