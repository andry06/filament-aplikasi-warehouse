<?php

namespace App\Filament\Resources\GoodReceiveResource\RelationManagers;

use Closure;
use Filament\Forms;
use App\Models\Item;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ItemVariant;
use App\Models\Transaction;
use Filament\Support\RawJs;
use App\Models\TransactionDetail;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Actions\Action;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;
use Pelmered\FilamentMoneyField\Forms\Components\MoneyInput;

class GoodReceiveItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactionDetails';

    protected static bool $isLazy = false;

    protected static ?string $title = 'Barang yang diterima';

    public static function getModelLabel(): string
    {
        return 'Barang yang diterima';
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
                            $set('item_id', null);
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
                                ->options(fn (Get $get) => $this->getItemOptions($get))
                                ->afterStateUpdated(function (Set $set, $state) {
                                    $set('item_variant_id', null);
                                    $set('unit', Item::find($state)?->unit);
                                })
                                ->reactive()
                                ->searchable(),
                            Forms\Components\Select::make('item_variant_id')
                                ->label('Warna')
                                ->options(fn (Get $get, $state) => $this->getItemVariantOptions($get, $state))
                                ->afterStateUpdated(function (Set $set, $state) {
                                    $set('price', ItemVariant::find($state)?->price);
                                })
                                ->reactive()
                                ->searchable(),
                            Forms\Components\TextInput::make('unit')
                                ->label('Satuan')
                                ->readOnly(),
                            Forms\Components\TextInput::make('price')
                                ->stripCharacters(',')
                                ->label('Harga')
                                ->afterStateUpdated(function (Set $set, $state, Get $get) {
                                    if (!$get('qty')) {
                                        return [];
                                    }
                                    $set('total_price', $state * $get('qty'));
                                })
                                ->reactive()
                                ->prefix('Rp')
                                ->suffixAction(
                                    Action::make('refreshPrice')
                                        ->label('Simpan ke Master Barang')
                                        ->icon('heroicon-m-document-arrow-up')
                                        ->tooltip('Simpan harga ke master barang')
                                        ->action(fn (Get $get) => $this->refreshItemPrice($get))
                                )
                                ->numeric(),
                            Forms\Components\TextInput::make('qty')
                                ->label('Jumlah')
                                ->numeric()
                                ->minValue(1)
                                ->required()
                                ->formatStateUsing(fn ($state) => trimDecimalZero($state))
                                ->afterStateUpdated(function (Set $set, $state, Get $get) {
                                    if (!$get('price')) {
                                        return [];
                                    }

                                    $set('total_price', $state * $get('price'));
                                })
                                ->reactive()
                                ->validationMessages([
                                    'required' => 'Jumlah oleh wajib diisi.',
                                ]),
                            Forms\Components\TextInput::make('total_price')
                                ->label('Total Harga')
                                ->formatStateUsing(fn ($state) => trimDecimalZero($state))
                                ->prefix('Rp')
                                ->disabled()
                                ->numeric(),
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
                Tables\Columns\TextColumn::make('unit')
                    ->label('Unit')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('qty')
                    ->formatStateUsing(fn ($state) => trimDecimalZero($state))
                    ->label('Jumlah')
                    ->alignRight()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Harga')
                    ->alignRight()
                    ->formatStateUsing(fn ($state) => rupiah($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total Harga')
                    ->alignRight()
                    ->formatStateUsing(fn ($state) => rupiah((int) $state))
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
                    ->modalHeading('Tambah Barang yang diterima')
                    ->label('Tambah Barang')
                    ->visible(fn ($livewire) => $livewire->ownerRecord->status !== 'approve')
                    ->closeModalByClickingAway(false)
                    ->action(function (array $data): void {
                        try {
                            if ($this->ownerRecord->status == 'approve') {
                                throw new Exception('Anda tidak dapat menambahkan barang karena status sudah approve.');
                            }

                            $this->ownerRecord->transactionDetails()->create($data + [
                                'type' => 'in'
                            ]);
                        } catch (\Exception $e) {
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

    protected function getItemOptions(Get $get): array
    {
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

        return Item::selectRaw("CONCAT(code, ' - ', name) as value, id")
                ->where('category', $category)
                ->whereHas('ItemVariants', function (Builder $query) use ($itemVariantIds) {
                    $query->whereNotIn('id', $itemVariantIds);
                })
                ->pluck('value', 'id')
                ->toArray();
    }

    protected function getItemVariantOptions(Get $get, ?string $state): array
    {
        $itemId = $get('item_id');
        if (!$itemId) {
            return [];
        }

        $itemVariantIds = $this->ownerRecord->transactionDetails()
            ->pluck('item_variant_id')
            ->toArray();

        if ($state) {
            $itemVariantIds = array_filter($itemVariantIds, fn($id) => $id != $state);
        }

        return ItemVariant::where('item_id', $itemId)
                ->whereNotIn('id', $itemVariantIds)
                ->pluck('color', 'id')
                ->toArray();
    }

    protected function refreshItemPrice(Get $get): void
    {
        if (! $get('item_variant_id')) {
            Notification::make()
                ->title('Gagal item belum dipilih.')
                ->danger()
                ->send();
            return;
        }

        $itemVariant = ItemVariant::find($get('item_variant_id'));

        $itemVariant->update([
            'price' => $get('price'),
        ]);

        Notification::make()
            ->title('Berhasil memperbarui harga master barang.')
            ->success()
            ->send();
    }
}
