<?php

namespace App\Filament\Resources\ProductionReturnResource\RelationManagers;

use Filament\Forms;
use App\Models\Item;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Transaction;
use App\Services\StockService;
use App\Models\TransactionDetail;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Services\ProductionAllocationService;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class ProductionReturnItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactionDetails';

    protected static bool $isLazy = false;

    protected static ?string $title = 'Barang yang Dikembalikan ke Gudang';


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(12)
                ->schema([
                    Forms\Components\Select::make('item_id')
                        ->label('Barang')
                        ->options(function (Get $get, $state) {

                            $projectId = $this->ownerRecord->project_id;
                            $itemVariantIds = $this->ownerRecord->transactionDetails()
                                ->pluck('item_variant_id')->toArray();

                            $transactionIds = Transaction::where('project_id', $projectId)
                                ->where('type', 'production_allocation')
                                ->where('status', 'approve')
                                ->pluck('id')->toArray();

                            return TransactionDetail::selectRaw('concat(items.code, " - ", items.name) as value, items.id')
                                ->leftJoin('items', 'transaction_details.item_id', '=', 'items.id')
                                ->whereNotIn('transaction_details.item_variant_id', $itemVariantIds)
                                ->whereIn('transaction_id', $transactionIds)
                                ->groupBy('transaction_details.item_id')
                                ->pluck('value', 'id')->toArray();
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

                            $projectId = $this->ownerRecord->project_id;
                            $itemVariantIds = $this->ownerRecord->transactionDetails()
                                ->pluck('item_variant_id')->toArray();

                            $transactionIds = Transaction::where('project_id', $projectId)
                                ->where('type', 'production_allocation')
                                ->where('status', 'approve')
                                ->pluck('id')->toArray();

                            return TransactionDetail::leftJoin('item_variants', 'transaction_details.item_variant_id', '=', 'item_variants.id')
                                ->whereNotIn('transaction_details.item_variant_id', $itemVariantIds)
                                ->whereIn('transaction_id', $transactionIds)
                                ->where('transaction_details.item_id', $itemId)
                                ->groupBy('transaction_details.item_variant_id')
                                ->pluck('color', 'item_variants.id')->toArray();
                    })
                    ->afterStateUpdated(function (Set $set, $state) {
                        if(!$state) {
                            return [];
                        }

                        $stockService = new StockService();
                        $projectId = $this->ownerRecord->project_id;
                        $stockItem = $stockService->getProductionStockOnProject($projectId, $state);

                        $set('stock', $stockItem);
                    })
                    ->reactive()->searchable(),
                Forms\Components\TextInput::make('unit')
                    ->label('Satuan')
                    ->readOnly(),
                Forms\Components\TextInput::make('stock')
                        ->label('Qty Tersedia')
                        ->afterStateHydrated(function (TextInput $component, Get $get) {
                            $projectId = $this->ownerRecord->project_id;
                            $itemVariantId = $get('item_variant_id');
                            if (!$projectId || ! $itemVariantId) {
                                return;
                            }
                            $stockService = new StockService();
                            $stockItem = $stockService->getProductionStockOnProject($projectId, $get('item_variant_id'));
                            $component->state($stockItem);
                        })
                        ->disabled(),
                Forms\Components\TextInput::make('qty')
                    ->label('Jumlah')
                    ->numeric()
                    ->maxValue(fn (Get $get) => $get('stock')) // ← batas maksimal dari stock
                    ->reactive()
                    ->minValue(0.01)
                    ->required()
                    ->validationMessages([
                        'required' => 'Jumlah wajib diisi.',
                        'min' => 'Jumlah minimal adalah 1.',
                        'max' => 'Jumlah tidak boleh melebihi qty yang tersedia.',
                    ]),
                Forms\Components\TextInput::make('note')
                                ->label('Catatan')
                ])->columns(3),
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
                Tables\Filters\SelectFilter::make('category')
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
                    ->modalHeading('Tambah Barang yang dialokasikan')
                    ->label('Tambah Barang')
                    ->visible(fn ($livewire) => $livewire->ownerRecord->status !== 'approve')
                    ->closeModalByClickingAway(false)
                    ->action(function (array $data): void {
                        try {
                            if ($this->ownerRecord->status == 'approve') {
                                throw new Exception('Anda tidak dapat menambahkan barang karena status sudah approve.');
                            }

                            $productionAllocationService = new ProductionAllocationService();
                            $productionAllocationService->addTransactionDetail($this->ownerRecord, $data);

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
