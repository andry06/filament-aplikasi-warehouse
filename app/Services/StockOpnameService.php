<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Item;
use App\Models\ItemVariant;
use App\Models\Transaction;
use Illuminate\Support\Arr;
use App\Services\StockService;
use PhpParser\Node\Expr\AssignOp\Mod;
use Illuminate\Database\Eloquent\Model;

class StockOpnameService
{
    public function addTransactionDetail(Model $transaction, array $data): void
    {
        $stockService = app(StockService::class);
        $systemStock = $stockService->getStockForUpdate($data['item_variant_id'], $transaction->warehouse_id);
        $diffStock =  $data['actual_stock'] - $systemStock;

        $transactionDetail = $transaction->transactionDetails()
            ->create(
                Arr::except($data, ['actual_stock']) + [
                    'qty' => abs($diffStock),
                    'type' => ($diffStock > 0) ? 'in' : 'out',
                    'price' => ItemVariant::find($data['item_variant_id'])?->price,
                ]
            );


        $transactionDetail->stockOpnameDetail()->create([
            'system_stock' => $systemStock,
            'actual_stock' => $data['actual_stock'],
            'diff_stock' => $diffStock
        ]);
    }

    public function editTransactionDetail(Model $transaction, Model $transactionDetail, array $data): void
    {

        if ($data['is_update_stock'] == true) {
            $stockService = app(StockService::class);
            $systemStock = $stockService->getStockForUpdate($data['item_variant_id'], $transaction->warehouse_id);
            $diffStock =  $data['actual_stock'] - $systemStock;

            $transactionDetail->update([
                'qty' => abs($diffStock),
                'type' => ($diffStock > 0) ? 'in' : 'out',
            ]);

            $transactionDetail->stockOpnameDetail()->update([
                'system_stock' => $systemStock,
                'actual_stock' => $data['actual_stock'],
                'diff_stock' => $diffStock
            ]);

        }else{
            $stockOpnameDetail = $transactionDetail->stockOpnameDetail;
            $systemStock = $stockOpnameDetail->system_stock;
            $diffStock =  $data['actual_stock'] - $systemStock;

            $transactionDetail->update([
                'qty' => abs($diffStock),
                'type' => ($diffStock > 0) ? 'in' : 'out',
            ]);

            $transactionDetail->stockOpnameDetail()->update([
                'system_stock' => $systemStock,
                'actual_stock' => $data['actual_stock'],
                'diff_stock' => $diffStock
            ]);
        }
    }

    public function deleteTransactionDetail(Model $transactionDetail): void
    {
        $transactionDetail->stockOpnameDetail()->delete();
        $transactionDetail->delete();
    }


    public function isNotAllowedCreate(): bool
    {
        return Transaction::where('status', 'draft')
            ->exists();
    }

    public function approve(Transaction $transaction): void
    {
        if ($transaction->status != 'draft') {
            throw new \Exception('Status bukan "draft", tidak bisa diapprove.');
        }

        $transactionDetails = $transaction->transactionDetails()->get();

        $stockService = app(StockService::class);

        foreach ($transactionDetails as $transactionDetail) {
            $beginStock = $stockService->getStockForUpdate($transactionDetail->item_variant_id, $transaction->warehouse_id);
            if($transactionDetail->type == 'out' && $transactionDetail->qty > $beginStock) {
                $item = Item::find($transactionDetail->item_id);
                $itemVariant = ItemVariant::find($transactionDetail->item_variant_id);
                throw new \Exception("Stok barang $item->code - $itemVariant->color tidak mencukupi.");
            }

            $operationApprove = $transactionDetail->type == 'in' ? 'plus' : 'minus';
            $stockService->updateStockItem($transaction, $transactionDetail, $beginStock, $operationApprove);
            $stockService->updateStockMutationForApprove($transaction, $transactionDetail);
        }

        $transaction->update(['status' => 'approve']);
    }

    public function cancelApprove(Transaction $transaction): void
    {
        if ($transaction->status != 'approve') {
            throw new \Exception('Status bukan "approve", tidak bisa dibatalkan.');
        }

        if ($this->isNotAllowedCancelApprove($transaction)) {
            throw new \Exception('Tidak dapat cancel approve stock opname ini, karena sudah terdapat transaksi yang baru.');
        }

        $stockService = app(StockService::class);
        $transactionDetails = $transaction->transactionDetails()->get();

        foreach ($transactionDetails as $transactionDetail) {
            $beginStock = $stockService->getStockForUpdate($transactionDetail->item_variant_id, $transaction->warehouse_id);
            $operationCancelApprove = $transactionDetail->type == 'in' ? 'minus' : 'plus';
            $stockService->updateStockItem($transaction, $transactionDetail, $beginStock, $operationCancelApprove);
            $stockService->updateStockMutationForCancelApprove($transaction, $transactionDetail);
        }

        $transaction->update(['status' => 'draft']);

    }

    public function isNotAllowedCancelApprove(Transaction $transaction): bool
    {
        $newTransaction = Transaction::where('type', 'stock_opname')
            ->where('warehouse_id', $transaction->warehouse_id)
            ->orderBy('date', 'desc')
            ->first();

        $dateTransaction = Carbon::parse($transaction->date);
        $dateNewTransaction = Carbon::parse($newTransaction->date);

        return $dateTransaction->lt($dateNewTransaction);
    }
}
