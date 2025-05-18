<?php

namespace App\Filament\Resources\ProductionReturnResource\Pages;

use App\Filament\Resources\ProductionReturnResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductionReturn extends EditRecord
{
    protected static string $resource = ProductionReturnResource::class;

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
