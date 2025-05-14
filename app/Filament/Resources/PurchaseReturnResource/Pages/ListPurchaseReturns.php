<?php

namespace App\Filament\Resources\PurchaseReturnResource\Pages;

use Filament\Actions;
use App\Models\Transaction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\PurchaseReturnResource;

class ListPurchaseReturns extends ListRecords
{
    protected static string $resource = PurchaseReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTableQuery(): ?Builder
    {
        return Transaction::select('transactions.*', 'suppliers.name as supplier_name')
            ->LeftJoin('suppliers', 'transactions.supplier_id', '=', 'suppliers.id')
            ->where('type', 'purchase_return');
    }
}
