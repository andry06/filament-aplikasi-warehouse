<?php

namespace App\Filament\Resources\StockMutationResource\Pages;

use App\Filament\Resources\StockMutationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStockMutation extends CreateRecord
{
    protected static string $resource = StockMutationResource::class;
}
