<?php

namespace App\Filament\Resources\StockMutationResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\StockMutationResource;
use App\Filament\Resources\StockMutationResource\RelationManagers\StockMutationDetailsRelationManager;
use Guava\FilamentModalRelationManagers\Actions\Action\RelationManagerAction;

class EditStockMutation extends EditRecord
{
    protected static string $resource = StockMutationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            // RelationManagerAction::make()
            //     ->label('Detail Transaksi')
            //     ->record($this->getRecord())
            //     ->relationManager(StockMutationDetailsRelationManager::make())

        ];
    }
}
