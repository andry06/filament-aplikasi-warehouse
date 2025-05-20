<?php

namespace App\Filament\Resources\StockOpnameResource\Pages;

use Filament\Actions;
use App\Models\Transaction;
use App\Services\StockOpnameService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\StockOpnameResource;

class ListStockOpnames extends ListRecords
{
    protected static string $resource = StockOpnameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
