<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\Project;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProjectTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected function getTableHeading(): string
    {
        return $this->ownerRecord->name.' | Total Material Cost : '. rupiah($this->ownerRecord->material_cost);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('project_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item_variant_id')
            ->columns([
                Tables\Columns\TextColumn::make('item_code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item_name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item_color')
                    ->label('Warna')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('qty_allocation')
                    ->label('Alokasi')
                    ->formatStateUsing(fn ($state) => trimDecimalZero($state))
                    ->alignRight()
                    ->sortable(),
                Tables\Columns\TextColumn::make('qty_return')
                    ->label('Retur')
                    ->formatStateUsing(fn ($state) => trimDecimalZero($state))
                    ->alignRight()
                    ->sortable(),
                Tables\Columns\TextColumn::make('qty_used')
                    ->label('Terpakai')
                    ->alignRight()
                    ->formatStateUsing(fn ($state) => trimDecimalZero($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Harga')
                    ->alignRight()
                    ->formatStateUsing(fn ($state) => rupiah($state))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('material_cost')
                    ->label('Material Cost')
                    ->alignRight()
                    ->sortable(false) // atau true jika kamu ingin urutkan secara custom
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->getStateUsing(function ($record) {
                        return rupiah((int) $record->qty_used * $record->price);
                    }),


            ])
            ->defaultSort('item_variant_id', 'asc')
            ->filters([
                //
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

        return Transaction::selectRaw('item_variants.id, items.id as item_id, items.code as item_code, items.name as item_name,
                item_variants.color as item_color,
                SUM(IF(transactions.type = "production_allocation", transaction_details.qty, 0)) AS qty_allocation,
                SUM(IF(transactions.type = "production_return", transaction_details.qty, 0)) AS qty_return,
                SUM(IF(transactions.type = "production_allocation", transaction_details.qty, 0)) - SUM(IF(transactions.type = "production_return", transaction_details.qty, 0)) AS qty_used,
                max(transaction_details.price) as price')
            ->join('transaction_details', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('item_variants', 'transaction_details.item_variant_id', '=', 'item_variants.id')
            ->join('items', 'item_variants.item_id', '=', 'items.id')
            ->where('transactions.project_id', '=', $this->ownerRecord->id)
            ->where('transactions.status', '=', 'approve')
            ->groupBy('transaction_details.item_variant_id');
    }
}
