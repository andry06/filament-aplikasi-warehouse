<?php

namespace App\Filament\Resources\StockOpnameResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StockOpnameItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactionDetails';

    protected static bool $isLazy = false;

    protected static ?string $title = 'Barang Stock Opname';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('item_detail_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item_detail_id')
            ->columns([
                Tables\Columns\TextColumn::make('item_detail_id'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                ->modalWidth('5xl')
                    ->createAnother(false)
                    ->modalSubmitActionLabel('Tambah')
                    ->modalHeading('Tambah Barang Stock Opname')
                    ->label('Tambah Barang')
                    ->visible(fn ($livewire) => $livewire->ownerRecord->status !== 'approve')
                    ->closeModalByClickingAway(false)
                    ->action(function (array $data): void {
                        // try {
                        //     if ($this->ownerRecord->status == 'approve') {
                        //         throw new Exception('Anda tidak dapat menambahkan barang karena status sudah approve.');
                        //     }

                        //     $purchaseReturnService = new PurchaseReturnService();
                        //     $purchaseReturnService->addTransactionDetail($this->ownerRecord, $data);

                        // } catch (\Exception $e) {
                        //     info($e);
                        //     Notification::make()
                        //         ->title('Gagal')
                        //         ->body($e->getMessage())
                        //         ->warning()
                        //         ->send();
                        // }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
