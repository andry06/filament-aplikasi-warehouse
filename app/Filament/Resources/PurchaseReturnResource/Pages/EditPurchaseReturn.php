<?php

namespace App\Filament\Resources\PurchaseReturnResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\DB;
use App\Services\TransactionService;
use App\Services\PurchaseReturnService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;;
use App\Filament\Resources\PurchaseReturnResource;

class EditPurchaseReturn extends EditRecord
{
    protected static string $resource = PurchaseReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record?->status == 'draft'),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }

    public function toggleApprove()
    {
        try {
            $transactionService = app(TransactionService::class);
            if ($transactionService->isNotAllowedApprove($this->record)) {
                throw new \Exception('Transaksi ini terkunci karena sudah terdapat stock opname setelah tanggal transaksi ini.');
            }
            $purchaseReturnService = app(PurchaseReturnService::class);
            DB::beginTransaction();
            if ($this->record?->status == 'draft') {
                $purchaseReturnService->approve($this->record);
                $message = 'Status berhasil diapprove';
            } else {
                $purchaseReturnService->cancelApprove($this->record);
                $message = 'Status berhasil menjadi draft kembali';
            }
            DB::commit();
            Notification::make()
                ->title($message)
                ->success()
                ->send();
            return redirect()->route('filament.admin.resources.purchase-returns.edit', [
                'record' => $this->record->id,
            ]);
        } catch (\Exception $e) {
            // info($e);
            DB::rollback();
            Notification::make()
                ->title('Gagal mengubah status')
                ->body($e->getMessage())
                ->warning()
                ->send();
        }
    }
}


