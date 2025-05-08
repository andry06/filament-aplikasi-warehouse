<?php

namespace App\Filament\Resources\GoodReceiveResource\Pages;

use App\Filament\Resources\GoodReceiveResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGoodReceive extends EditRecord
{
    protected static string $resource = GoodReceiveResource::class;

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn ($livewire) => $livewire->record?->status == 'draft'),
        ];
    }
}
