<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Warehouse;
use Filament\Tables\Table;
use App\Models\ItemVariant;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Indicator;
use Illuminate\Database\Eloquent\Builder;
use App\Models\StockMutation\StockMutation;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\StockMutationResource\Pages;
use App\Filament\Resources\StockMutationResource\RelationManagers;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Guava\FilamentModalRelationManagers\Actions\Table\RelationManagerAction;
use App\Filament\Resources\StockMutationResource\RelationManagers\StockMutationDetailsRelationManager;
use Filament\Forms\Get;

class StockMutationResource extends Resource
{
    protected static ?string $model = ItemVariant::class;

    protected static ?string $navigationIcon = 'heroicon-o-square-3-stack-3d';

    protected static ?string $modelLabel = 'Mutasi Stok';

    protected static ?string $navigationGroup = 'Manajemen Stok';

    protected static ?int $navigationSort = 13;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                    ->label('Warna')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Harga')
                    ->alignRight()
                    ->formatStateUsing(fn ($state) => rupiah($state))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('begin_stock')
                    ->formatStateUsing(fn ($state) => trimDecimalZero($state))
                    ->alignRight()
                    ->label('Stok Awal')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_qty_in')
                    ->formatStateUsing(fn ($state) => trimDecimalZero($state))
                    ->alignRight()
                    ->label('Jumlah Masuk')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_qty_out')
                    ->formatStateUsing(fn ($state) => trimDecimalZero($state))
                    ->alignRight()
                    ->label('Jumlah Keluar')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ending_stock')
                    ->formatStateUsing(fn ($state) => trimDecimalZero($state))
                    ->alignRight()
                    ->label('Stok Akhir')
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Unit')
                    ->sortable(),
            ])
            ->defaultPaginationPageOption(5)
            ->recordUrl(null)
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Gudang')
                    ->options(Warehouse::all()->pluck('name', 'id'))
                    ->query(fn () => null)
                    ->selectablePlaceholder(false)
                    ->default(fn () => Warehouse::orderBy('id')->value('id'))
                    ->searchable(),
                DateRangeFilter::make('date')
                    ->label('Filter Tanggal')
                    ->query(fn () => null)
                    ->startDate(Carbon::now()->startOfMonth())
                    ->endDate(Carbon::now())
                    ->maxDate(Carbon::now())
                    ->autoApply()
                    ->indicateUsing(function (array $data): array {
                        [$startDate, $endDate] = explode(' - ', $data['date']);
                        return [
                            Indicator::make('Filter Tanggal: Periode ' . $startDate. ' - ' . $endDate)
                                ->removable(false),
                        ];
                    }),
                Tables\Filters\SelectFilter::make('category')
                    ->multiple()
                    ->query(fn () => null)
                    ->options([
                        'asset' => 'Asset',
                        'main_material' => 'Main Material',
                        'accessories' => 'Accessories',
                    ])
                ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                RelationManagerAction::make('stock-mutation-details-relation-manager')
                    ->label('Detail')
                    ->icon('heroicon-o-magnifying-glass-plus')
                    ->hiddenLabel()
                    ->iconSize('md')
                    ->modalWidth('6xl')
                    ->modalHeading(fn ($record) => 'Detail Transaksi  '.session('filter_stock_mutation')['dates']) // <<< tampilkan nama dari kolom `name`
                    ->closeModalByClickingAway(false)
                    ->relationManager(StockMutationDetailsRelationManager::make()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // TextEntry::make('title')
                //     ->suffixAction(RelationManagerAction::make()
                //         ->label('Detail Transaksi')
                //         ->relationManager(StockMutationDetailsRelationManager::make()))
            ])
        // ...
        ;
    }



    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockMutations::route('/'),
            'create' => Pages\CreateStockMutation::route('/create'),
            'edit' => Pages\EditStockMutation::route('/{record}/edit'),
        ];
    }
}
