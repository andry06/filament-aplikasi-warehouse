<?php

namespace App\Filament\Resources\StockOpnameResource\Pages;

use Filament\Actions;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\StockOpnameResource;

class CreateStockOpname extends CreateRecord
{
    protected static string $resource = StockOpnameResource::class;

        protected function getFormActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $purchaseReturnNumber = TransactionService::generateStockOpnameNumber();
        $data['user_id'] = auth()->id();
        $data['number'] = $purchaseReturnNumber['number'];
        $data['counter'] = $purchaseReturnNumber['counter'];
        $data['status'] = 'draft';
        $data['type'] = 'stock_opname';

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Simpan manual
        $transction = Transaction::create($data);

        return $transction;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Berhasil')
            ->body('Data header pengembalian barang ke pemasok berhasil ditambahkan.');
    }

}
