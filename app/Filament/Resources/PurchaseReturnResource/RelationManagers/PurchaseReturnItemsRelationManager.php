<?php

namespace App\Filament\Resources\PurchaseReturnResource\RelationManagers;

use Filament\Forms;
use App\Models\Item;
use Filament\Tables;
use App\Models\Stock;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ItemVariant;
use App\Models\Transaction;
use App\Services\StockService;
use App\Models\TransactionDetail;
use App\Services\TransactionService;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class PurchaseReturnItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'TransactionDetails';

    protected static bool $isLazy = false;

    protected static ?string $title = 'Barang yang dikembalikan';

    public static function getModelLabel(): string
    {
        return 'Barang yang dikembalikan';
    }

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
                        ->afterStateUpdated(function (Set $set, $state) {

                            $set('qty', null);
                            $set('item_variant_id', null);
                        })
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
                                    if (!$category) {
                                        return [];
                                    }

                                    $warehouseId = $this->ownerRecord->warehouse_id;
                                    $itemVariantIds = $this->ownerRecord->transactionDetails()
                                        ->pluck('item_variant_id')->toArray();

                                    return Item::selectRaw("CONCAT(code, ' - ', name) as value, id")
                                        ->whereHas('ItemVariants', function (Builder $query) use ($itemVariantIds, $warehouseId) {
                                            $query->whereNotIn('id', $itemVariantIds)
                                                ->whereHas('stocks', function (Builder $query) use ($warehouseId)  {
                                                    $query->where('warehouse_id', $warehouseId);
                                                });
                                        })->where('category', $category)
                                        ->pluck('value', 'id');
                                })
                                ->afterStateUpdated(function (Set $set, $state) {
                                    $set('item_variant_id', null);
                                    $set('qty', null);
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

                                    $warehouseId = $this->ownerRecord->warehouse_id;
                                    $itemVariantIds = $this->ownerRecord->transactionDetails()
                                        ->pluck('item_variant_id')->toArray();

                                    return ItemVariant::whereNotIn('id', $itemVariantIds)
                                                ->whereHas('stocks', function (Builder $query) use ($warehouseId)  {
                                                    $query->where('warehouse_id', $warehouseId);
                                            })->where('item_id', $itemId)
                                            ->pluck('color', 'id');
                                })
                                ->afterStateUpdated(function (Set $set, $state) {
                                    $warehouseId = $this->ownerRecord->warehouse_id;
                                    $stockItem = Stock::where('warehouse_id', $warehouseId)
                                        ->where('item_variant_id', $state)
                                        ->first();

                                    $set('stock', $stockItem?->stock ?? 0);
                                })
                                ->reactive()->searchable(),
                            Forms\Components\TextInput::make('unit')
                                ->label('Satuan')
                                ->readOnly(),
                            Forms\Components\TextInput::make('stock')
                                ->label('Stok Gudang')
                                ->disabled(),
                            Forms\Components\TextInput::make('qty')
                                ->label('Jumlah')
                                ->numeric()
                                ->maxValue(fn (Get $get) => $get('stock')) // ← batas maksimal dari stock
                                ->reactive()
                                ->minValue(1)
                                ->required()
                                ->validationMessages([
                                    'required' => 'Jumlah wajib diisi.',
                                    'min' => 'Jumlah minimal adalah 1.',
                                    'max' => 'Jumlah tidak boleh melebihi stok yang tersedia.',
                                ]),
                            Forms\Components\TextInput::make('note')
                                ->label('Catatan')
                        ])->columnSpan(10)->columns(3),
                    ]),

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
                Tables\Columns\TextColumn::make('qty')
                    ->label('Jumlah')
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Unit')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total Harga')
                    ->money('IDR', locale: 'id')
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
                    ->modalHeading('Tambah Barang yang dikembalikan')
                    ->label('Tambah Barang')
                    ->visible(fn ($livewire) => $livewire->ownerRecord->status !== 'approve')
                    ->closeModalByClickingAway(false)
                    ->action(function (array $data): void {
                        try {
                            if ($this->ownerRecord->status == 'approve') {
                                throw new Exception('Anda tidak dapat menambahkan barang karena status sudah approve.');
                            }

                            $stockService = new StockService();
                            $stock = $stockService->getStock($data['item_variant_id'], $this->ownerRecord?->warehouse_id);
                            if ($data['qty'] > $stock) {
                                throw new Exception('Stok tidak mencukupi.');
                            }

                            $this->ownerRecord->transactionDetails()
                                ->create($data + [
                                    'price' => ItemVariant::find($data['item_variant_id'])?->price
                                ]);
                        } catch (\Exception $e) {
                            info($e);
                            Notification::make()
                                ->title('Gagal')
                                ->body($e->getMessage())
                                ->warning()
                                ->send();
                        }

                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                ->label('')
                ->tooltip('Edit')
                ->iconSize('md')
                ->modalWidth('5xl')
                ->visible(fn ($livewire) => $livewire->ownerRecord->status !== 'approve'),
            Tables\Actions\DeleteAction::make()
                ->label('')
                ->tooltip('Hapus')
                ->iconSize('md')
                ->visible(fn ($livewire) => $livewire->ownerRecord->status !== 'approve'),
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
                (transaction_details.qty * transaction_details.price) as total_price, items.category
            ')
            ->leftJoin('item_variants', 'transaction_details.item_variant_id', '=', 'item_variants.id')
            ->leftJoin('items', 'item_variants.item_id', '=', 'items.id')
            ->where('transaction_details.transaction_id', '=', $this->ownerRecord->id);
    }

}
