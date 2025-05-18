<?php

namespace App\Filament\Resources\GoodReceiveResource\Pages;

use App\Filament\Resources\GoodReceiveResource;
use App\Models\Transaction;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListGoodReceives extends ListRecords
{
    protected static string $resource = GoodReceiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTableQuery(): ?Builder
    {
        info($this->tableFilters);
        return Transaction::select('transactions.*', 'suppliers.name as supplier_name')
            ->LeftJoin('suppliers', 'transactions.supplier_id', '=', 'suppliers.id')
            ->where('type', 'purchase_in');
    }
}
