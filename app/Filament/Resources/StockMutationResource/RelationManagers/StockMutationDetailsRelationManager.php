<?php

namespace App\Filament\Resources\StockMutationResource\RelationManagers;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\StockMutation;
use App\Models\TransactionDetail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class StockMutationDetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockMutations';
    protected function getTableHeading(): string
    {
        return $this->ownerRecord->item_full_name;
    }

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
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->alignCenter()
                    ->dateTime('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('number')
                    ->label('No Transaksi')
                    ->sortable(),
                Tables\Columns\TextColumn::make('qty')
                    ->label('Qty')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->formatStateUsing(fn ($state) => strtoupper($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('remaks')
                    ->label('Keterangan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('note')
                    ->label('Catatan')
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d F Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d F Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
            ])
            ->defaultSort('date', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe Transaksi')
                    ->attribute('transaction_details.type')
                    ->options([
                        'in' => 'IN',
                        'out' => 'OUT',
                    ]),
                Tables\Filters\SelectFilter::make('category_type')
                    ->label('Kategori Transaksi')
                    ->attribute('transactions.type')
                    ->options([
                        'purchase_in' => 'Belanja - Barang Masuk',
                        'purchase_return' => 'Belanja - Return Barang',
                        'production_allocation' => 'Produksi - Pengeluaran Barang',
                        'production_return' => 'Produksi - Pengembalian Barang',
                    ])
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public function getTableQuery(): ?Builder
    {

        $sessionFilters = session('filter_stock_mutation');
        $warehouseId = $sessionFilters['warehouse_id'];
        $dates = $sessionFilters['dates'];
        [$start, $end] = explode(' - ', $dates);
        $startDate = Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
        $endDate = Carbon::createFromFormat('d/m/Y', $end)->endOfDay();
        $itemVariantId = $this->ownerRecord->id;

        return TransactionDetail::selectRaw("transaction_details.id, transaction_details.type, transactions.created_at, transactions.updated_at,
                transactions.date, transactions.number, transaction_details.qty, transaction_details.type, IFNULL(transaction_details.note, '-') AS note,
                CASE
                    WHEN transactions.type = 'purchase_in' THEN CONCAT('Dari : ', suppliers.name)
                    WHEN transactions.type = 'purchase_return' THEN CONCAT('Ke : ', suppliers.name)
                    WHEN transactions.type = 'production_allocation' OR transactions.type = 'production_out' THEN CONCAT('Produksi :  ', projects.name)
                    WHEN transactions.type = 'stock_opname'  THEN CONCAT('Stok Opname ( Aktual : ', stock_opname_details.actual_stock, ' Sistem : ', stock_opname_details.system_stock, ' )')
                    ELSE 'Lainnya'
                END AS 'remaks'
            ")
            ->leftJoin('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->leftJoin('suppliers', 'transactions.supplier_id', '=', 'suppliers.id')
            ->leftJoin('projects', 'transactions.project_id', '=', 'projects.id')
            ->leftJoin('stock_opname_details', 'transaction_details.id', '=', 'stock_opname_details.transaction_detail_id')
            ->where('transaction_details.item_variant_id', '=', $itemVariantId)
            ->where('transactions.warehouse_id', '=', $warehouseId)
            ->whereBetween('transactions.date', [$startDate, $endDate]);
    }

}
