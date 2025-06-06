<?php

namespace App\Filament\Resources\GoodReceiveResource\Pages;

use Filament\Actions;
use App\Models\Transaction;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use App\Services\TransactionService;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\GoodReceiveResource;

class CreateGoodReceive extends CreateRecord
{
    protected static string $resource = GoodReceiveResource::class;

    protected function getFormActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $goodReceiveNumber = TransactionService::generateGoodReceiveNumber();
        $data['user_id'] = auth()->id();
        $data['number'] = $goodReceiveNumber['number'];
        $data['counter'] = $goodReceiveNumber['counter'];
        $data['status'] = 'draft';
        $data['type'] = 'purchase_in';

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
            ->body('Data header penerimaan barang berhasil ditambahkan.');
    }



}
