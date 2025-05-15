<?php

namespace App\Filament\Resources\ProductionAllocationResource\Pages;

use Filament\Actions;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ProductionAllocationResource;

class CreateProductionAllocation extends CreateRecord
{
    protected static string $resource = ProductionAllocationResource::class;

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
        $data['type'] = 'production_allocation';

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
            ->body('Data header alokasi barang ke produksi berhasil ditambahkan.');
    }

}

