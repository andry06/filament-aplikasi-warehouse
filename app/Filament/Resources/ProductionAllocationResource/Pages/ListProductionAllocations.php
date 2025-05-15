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


}
