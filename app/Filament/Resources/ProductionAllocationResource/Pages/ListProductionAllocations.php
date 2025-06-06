<?php

namespace App\Filament\Resources\ProductionAllocationResource\Pages;

use Filament\Actions;
use App\Models\Transaction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProductionAllocationResource;

class ListProductionAllocations extends ListRecords
{
    protected static string $resource = ProductionAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTableQuery(): ?Builder
    {
        return Transaction::select('transactions.*', 'projects.name as project_name')
            ->LeftJoin('projects', 'transactions.project_id', '=', 'projects.id')
            ->where('type', 'production_allocation');
    }
}
