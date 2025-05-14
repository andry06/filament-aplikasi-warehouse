<?php

namespace App\Filament\Resources\PurchaseReturnResource\Pages;

use Filament\Actions;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use App\Services\TransactionService;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\PurchaseReturnResource;

class CreatePurchaseReturn extends CreateRecord
{
    protected static string $resource = PurchaseReturnResource::class;

    protected function getFormActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $purchaseReturnNumber = TransactionService::generatePurchaseReturnNumber();
        $data['user_id'] = auth()->id();
        $data['number'] = $purchaseReturnNumber['number'];
        $data['counter'] = $purchaseReturnNumber['counter'];
        $data['status'] = 'draft';
        $data['type'] = 'purchase_return';

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
            ->body('Data header pengembalian barang berhasil ditambahkan.');
    }


}
