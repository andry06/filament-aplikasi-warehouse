<?php

namespace App\Filament\Resources\StockOpnameResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\DB;
use App\Services\StockOpnameService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\StockOpnameResource;

class EditStockOpname extends EditRecord
{
    protected static string $resource = StockOpnameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn ($livewire) => $livewire->record?->status == 'draft')
                ->action(fn () => $this->deleteRecord()),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }

    public function toggleApprove()
    {
        try {
            DB::beginTransaction();
            $stockOpnameService = app(StockOpnameService::class);
            if ($this->record?->status == 'draft') {
                $stockOpnameService->approve($this->record);
                $message = 'Status berhasil diapprove';
            } else {
                $stockOpnameService->cancelApprove($this->record);
                $message = 'Status berhasil menjadi draft kembali';
            }
            DB::commit();
            Notification::make()
                ->title($message)
                ->success()
                ->send();
            return redirect()->route('filament.admin.resources.stock-opnames.edit', [
                    'record' => $this->record->id,
                ]);
        } catch (\Exception $e) {
            info($e);
            DB::rollback();
            Notification::make()
                ->title('Gagal mengubah status')
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

            $transactionDetails = $this->record->transactionDetails()->get();
            foreach($transactionDetails as $transactionDetail){
                $transactionDetail->stockOpnameDetail->delete();
                $transactionDetail->delete();
            }

            $this->record->delete();
            Notification::make()
                    ->title('Data berhasil dihapus.')
                    ->success()
                    ->send();
            return redirect()->route('filament.admin.resources.stock-opnames.index');
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
