<?php

namespace App\Filament\Resources\ProductionAllocationResource\Pages;

use App\Filament\Resources\ProductionAllocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductionAllocation extends EditRecord
{
    protected static string $resource = ProductionAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn ($livewire) => $livewire->record?->status == 'draft'),
        ];
    }


    protected function getFormActions(): array
    {
        return [];
    }
}
