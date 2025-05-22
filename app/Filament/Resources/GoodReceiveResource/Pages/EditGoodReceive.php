<?php

namespace App\Filament\Resources\GoodReceiveResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\DB;
use App\Services\GoodReceiveService;
use App\Services\TransactionService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\GoodReceiveResource;

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
                ->visible(fn ($livewire) => $livewire->record?->status == 'draft')
                ->action(fn () => $this->deleteRecord())
        ];
    }

    public function toggleApprove()
    {
        try {
            $transactionService = app(TransactionService::class);
            if ($transactionService->isNotAllowedApprove($this->record)) {
                throw new \Exception('Transaksi ini terkunci karena sudah terdapat stock opname setelah tanggal transaksi ini.');
            }
            $goodReceiveService = app(GoodReceiveService::class);
            DB::beginTransaction();
            if ($this->record?->status == 'draft') {
                $goodReceiveService->approve($this->record);
                $message = 'Status berhasil diapprove';
            } else {
                $goodReceiveService->cancelApprove($this->record);
                $message = 'Status berhasil menjadi draft kembali';
            }
            DB::commit();
            Notification::make()
                ->title($message)
                ->success()
                ->send();
            return redirect()->route('filament.admin.resources.good-receives.edit', [
                'record' => $this->record->id,
            ]);
        } catch (\Exception $e) {
            // info($e);
            DB::rollback();
            Notification::make()
                ->title('Cancel Approve gagal.')
                ->body($e->getMessage())
                ->warning()
                ->send();
        }
    }

    public function deleteRecord()
    {
        try {
            if ($this->record?->status == 'approve') {
                throw new \Exception('Data tidak dapat dihapus karena statusnya sudah diapprove.');
            }

            $this->record->transactionDetails()->delete();
            $this->record->delete();

            Notification::make()
                    ->title('Data berhasil dihapus.')
                    ->success()
                    ->send();
            return redirect()->route('filament.admin.resources.good-receives.index');
        } catch (\Exception $e) {
            info($e);
            Notification::make()
                ->title('Gagal menghapus data.')
                ->body($e->getMessage())
                ->warning()
                ->send();
        }
    }


}
