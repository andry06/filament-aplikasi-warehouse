<?php

namespace App\Filament\Resources\StockOpnameResource\RelationManagers;

use Filament\Forms;
use App\Models\Item;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ItemVariant;
use App\Services\StockService;
use App\Models\TransactionDetail;
use App\Services\StockOpnameService;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Actions\Action;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Model;

class StockOpnameItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactionDetails';

    protected static bool $isLazy = false;

    protected static ?string $title = 'Barang Stock Opname';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
            Forms\Components\Grid::make(12)
                ->schema([
                    Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Radio::make('category')
                        ->label('Kategori')
                        ->options([
                            'asset' => 'Aset',
                            'main_material' => 'Material Utama',
                            'accessories' => 'Aksesoris',
                        ])
                        ->live()
                        ->required(),
                    ])
                    ->columnSpan(2),
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Select::make('item_id')
                                ->label('Barang')
                                ->options(function (Get $get, $state) {

                                    $category = $get('category');
                                    $itemVariantId = $get('item_variant_id');
                                    if (!$category) {
                                        return [];
                                    }

                                    $itemVariantIds = $this->ownerRecord->transactionDetails()
                                        ->pluck('item_variant_id')->toArray();

                                    // Hapus item aktif dari daftar blacklist agar tetap muncul di options
                                    if ($itemVariantId) {
                                        $itemVariantIds = array_filter($itemVariantIds, fn($id) => $id != $itemVariantId);
                                    }

                                    return  Item::selectRaw("CONCAT(items.code, ' - ', items.name) as value, items.id")
                                        ->whereHas('ItemVariants', function (Builder $query) use ($itemVariantIds) {
                                            $query->whereNotIn('id', $itemVariantIds);
                                        })->where('category', $category)
                                        ->pluck('value', 'id');

                                })
                                ->afterStateUpdated(function (Set $set, $state) {
                                    $set('unit', Item::find($state)?->unit);
                                })
                                ->reactive()
                                ->searchable(),
                            Forms\Components\Select::make('item_variant_id')
                                ->label('Warna')
                                ->options(function (Get $get, $state) {
                                    $itemId = $get('item_id');
                                    if (!$itemId) {
                                        return [];
                                    }

                                    $itemVariantIds = $this->ownerRecord->transactionDetails()
                                        ->pluck('item_variant_id')->toArray();

                                    if ($state) {
                                        $itemVariantIds = array_filter($itemVariantIds, fn($id) => $id != $state);
                                    }

                                    return ItemVariant::whereNotIn('id', $itemVariantIds)
                                            ->where('item_id', $itemId)
                                            ->pluck('color', 'id');
                                })
                                ->afterStateUpdated(function (Set $set, $state) {
                                    $warehouseId = $this->ownerRecord->warehouse_id;
                                    if(!$state || !$warehouseId){
                                        return [];
                                    }

                                    $stockService = new StockService();
                                    $stock = $stockService->getStock($state, $warehouseId);

                                    $set('system_stock', $stock);
                                })
                                ->reactive()
                                ->searchable(),
                            Forms\Components\TextInput::make('unit')
                                ->label('Satuan')
                                ->readOnly(),
                            Forms\Components\TextInput::make('system_stock')
                                ->label('Stok Sistem')
                                ->afterStateHydrated(function (TextInput $component, Get $get) {
                                    $warehouseId = $this->ownerRecord->warehouse_id;
                                    if (!$get('item_variant_id')){
                                        return;
                                    }
                                    $stockService = new StockService();
                                    $stock = $stockService->getStock($get('item_variant_id'), $warehouseId);
                                    $component->state($stock);
                                })
                                ->suffixAction(
                                    Action::make('refreshPrice')
                                        ->label('Perbarui stok saat ini')
                                        ->icon('heroicon-m-arrow-path')
                                        ->tooltip('Perbarui stok saat ini')
                                        ->visible(fn (string $context) => $context === 'edit')
                                        ->action(function (Set $set, Get $get) {

                                            if (! $get('item_variant_id')) {
                                                Notification::make()
                                                    ->title('Gagal item belum dipilih.')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            $stockService = new StockService();
                                            $stockSystem = $stockService->getStock($get('item_variant_id'), $this->ownerRecord->warehouse_id);

                                            $actualStock = $get('actual_stock') ?? 0;
                                            $set('system_stock', $stockSystem);
                                            $set('diff_stock', ($actualStock - $stockSystem));
                                            $set('is_update_stock', true);

                                            Notification::make()
                                            ->title('Berhasil memperbarui stock .')
                                            ->body('Agar data didatabase berubah silakan klik simpan.')
                                            ->success()
                                            ->send();

                                        })
                                )
                                ->readOnly(),
                            Forms\Components\Hidden::make('is_update_stock')
                                ->default(false) // atau false, sesuai kebutuhan
                                ->visible(fn (string $context) => $context === 'edit'),
                            Forms\Components\TextInput::make('actual_stock')
                                ->label('Stock Aktual')
                                ->numeric()
                                ->live()
                                ->minValue(0.01)
                                ->required()
                                ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                    $set('diff_stock', $state - $get('system_stock'));
                                })
                                ->validationMessages([
                                    'required' => 'Stock Aktual wajib diisi.',
                                    'min' => 'Stock Aktual minimal adalah 0.01.',
                                ]),
                            Forms\Components\TextInput::make('diff_stock')
                                ->label('Selisih Stok')
                                ->readOnly(),
                        ])->columnSpan(10)->columns(3),
                    ]),
                    Forms\Components\TextInput::make('note')
                        ->columnSpanFull()
                        ->label('Catatan')
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item_detail_id')
            ->emptyStateHeading('Belum ada Barang yang ditambahkan.')
            ->emptyStateDescription('Tambahkan Barang yang diterima untuk memulai.')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('color')
                    ->label('Color')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('system_stock')
                    ->label('Stok Sistem')
                    ->formatStateUsing(fn ($state) => trimDecimalZero($state))
                    ->alignment('right')
                    ->sortable(),
                Tables\Columns\TextColumn::make('actual_stock')
                    ->label('Stok Aktual')
                    ->alignment('right')
                    ->formatStateUsing(fn ($state) => trimDecimalZero($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('diff_stock')
                    ->label('Selisih Stok')
                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : null))
                    ->formatStateUsing(function ($state) {
                        if ($state > 0) {
                            return '+ ' . trimDecimalZero($state);
                        } elseif ($state < 0) {
                            return '- '. trimDecimalZero(abs($state)); // sudah minus otomatis
                        }
                        return '0'; // untuk nilai nol
                    })
                    ->alignment('right')
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Unit')
                    ->sortable(),
                Tables\Columns\TextColumn::make('note')
                    ->label('Catatan')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d F Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d F Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                SelectFilter::make('category')
                    ->multiple()
                    ->options([
                        'asset' => 'Asset',
                        'main_material' => 'Main Material',
                        'accessories' => 'Accessories',
                    ])
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
                        try {
                            if ($this->ownerRecord->status == 'approve') {
                                throw new Exception('Anda tidak dapat menambahkan barang karena status sudah approve.');
                            }

                            $stockOpnameService = new StockOpnameService();
                            $stockOpnameService->addTransactionDetail($this->ownerRecord, $data);

                        } catch (\Exception $e) {
                            info($e);
                            Notification::make()
                                ->title('Gagal')
                                ->body($e->getMessage())
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Edit')
                    ->iconSize('md')
                    ->modalWidth('5xl')
                    ->visible(fn ($livewire) => $livewire->ownerRecord->status !== 'approve')
                    ->action(function (Model $record, array $data): void {
                        if ($this->ownerRecord->status == 'approve') {
                            throw new Exception('Anda tidak dapat menambahkan barang karena status sudah approve.');
                        }

                        $stockOpnameService = new StockOpnameService();
                        $stockOpnameService->editTransactionDetail($this->ownerRecord, $record, $data);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Hapus')
                    ->iconSize('md')
                    ->visible(fn ($livewire) => $livewire->ownerRecord->status !== 'approve')
                    ->action(function (Model $record): void {
                        if ($this->ownerRecord->status == 'approve') {
                            throw new Exception('Anda tidak dapat menghapus barang karena status sudah approve.');
                        }
                        $stockOpnameService = new StockOpnameService();
                        $stockOpnameService->deleteTransactionDetail($record);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function getTableQuery(): ?Builder
    {
        return TransactionDetail::selectRaw('transaction_details.*, items.code, items.name, item_variants.color,
                items.category, stock_opname_details.system_stock, stock_opname_details.actual_stock, stock_opname_details.diff_stock
            ')
            ->leftJoin('item_variants', 'transaction_details.item_variant_id', '=', 'item_variants.id')
            ->leftJoin('items', 'item_variants.item_id', '=', 'items.id')
            ->leftJoin('stock_opname_details', 'transaction_details.id', '=', 'stock_opname_details.transaction_detail_id')
            ->where('transaction_details.transaction_id', '=', $this->ownerRecord->id);
    }
}
