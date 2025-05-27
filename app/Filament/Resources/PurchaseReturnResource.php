<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Supplier;
use Filament\Forms\Form;
use App\Models\Warehouse;
use Filament\Tables\Table;
use Illuminate\Support\Js;
use App\Models\Transaction;
use Filament\Resources\Resource;
use App\Services\TransactionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PurchaseReturnResource\Pages;
use App\Filament\Resources\PurchaseReturnResource\RelationManagers;

class PurchaseReturnResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-backward';

    protected static ?string $modelLabel = 'Barang Kembali';

    protected static ?string $navigationGroup = 'Belanja';

    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Forms\Components\Fieldset::make(fn ($livewire) => $livewire->record?->status == 'approve' ? 'Header - Approve ' : 'Header - Draft')
                ->schema([
                    Forms\Components\TextInput::make('number')
                        ->label('No Transaksi')
                        ->default(fn () => TransactionService::generatePurchaseReturnNumber()['number'])
                        ->disabled()
                        ->required(),
                    Forms\Components\DatePicker::make('date')
                        ->default(now())
                        ->minDate(function () {
                            $transaction = Transaction::where('type', 'stock_opname')->orderBy('date', 'desc')->first();
                            return $transaction != null ? $transaction->date : null;
                        })
                        ->required()
                        ->readOnly(fn ($livewire) => $livewire->record?->status == 'approve'),
                    Forms\Components\Select::make('warehouse_id')
                        ->label('Gudang')
                        ->options(Warehouse::all()->pluck('name', 'id'))
                        ->disabled(fn ($livewire) => $livewire->record?->status == 'approve')
                        ->default(fn () => Warehouse::orderBy('id')->value('id')),
                    Forms\Components\Select::make('supplier_id')
                        ->label('Supplier')
                        ->options(Supplier::all()->pluck('name', 'id'))
                        ->disabled(fn ($livewire) => $livewire->record?->status == 'approve')
                        ->searchable(),
                    Forms\Components\TextInput::make('reference_number')
                        ->label('No SJ Pengembalian')
                        ->required()
                        ->readOnly(fn ($livewire) => $livewire->record?->status == 'approve')
                        ->validationMessages([
                            'required' => 'No SJ Pengembalian wajib diisi.',
                        ]),
                    Forms\Components\TextInput::make('pic_field')
                        ->label('Dikirim oleh')
                        ->required()
                        ->readOnly(fn ($livewire) => $livewire->record?->status == 'approve')
                        ->validationMessages([
                            'required' => 'Dikirim oleh wajib diisi.',
                        ]),
                    Forms\Components\TextInput::make('note')
                        ->label('Catatan')->columnSpan(['lg' => 2, 'md' => 3, 'sm' => 1]),
                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('Buat')
                            ->label('Buat')
                            ->submit('create')
                            ->color('primary')
                            ->visible(fn ($livewire) => $livewire->record == null),
                        Forms\Components\Actions\Action::make('save')
                            ->label('Simpan')
                            ->submit('save')
                            ->visible(fn ($livewire) => $livewire->record?->status == 'draft')
                            ->color('primary'),
                        Forms\Components\Actions\Action::make('approve')
                            ->label(fn ($livewire) => $livewire->record?->status == 'draft' ? 'Approve' : 'Cancel Approve')
                            ->color(fn ($livewire) => $livewire->record?->status == 'draft' ? 'success' : 'warning')
                            ->requiresConfirmation()
                            ->visible(fn ($livewire) => $livewire->record != null)
                            ->action(fn ($livewire) => $livewire->toggleApprove()),
                        Forms\Components\Actions\Action::make('print')
                            ->label('Cetak')
                            ->color('primary')
                            ->extraAttributes([
                                'target' => '_blank'
                            ])
                            ->url(function ($livewire) {
                                return route('print.purchase-returns', $livewire->record?->id);
                            })->visible(fn ($livewire) => $livewire->record != null),
                        Forms\Components\Actions\Action::make('cancel')
                            ->label('Batal')
                            ->alpineClickHandler(
                                "document.referrer ? window.history.back() : window.location.href = " . Js::from(
                                    url()->previous() ?? static::getResource()::getUrl()
                                )
                            )
                            ->visible(fn ($livewire) => $livewire->record == null)
                            ->color('gray'),

                    ])->columnSpanFull(),

                ])
                ->columns([
                    'sm' => 2,
                    'md' => 3,
                    'xl' => 4,
                    '2xl' => 5,
                ])
            ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('No Transaksi')
                    ->color('primary')
                    ->weight('bold')
                    ->wrap()
                    ->url(fn($record) => PurchaseReturnResource::getUrl('edit', ['record' => $record]))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->dateTime('d F Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('No SJ / Invoice')
                    ->searchable(),
                Tables\Columns\TextColumn::make('supplier_name')
                    ->label('Nama Pemasok')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where("suppliers.name", "LIKE", "%$search%");
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('pic_field')
                    ->label('Dikirim oleh'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'warning',
                        'approve' => 'success',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),
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
            ->defaultSort('number', 'desc')
            ->recordUrl(null)
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Pemasok')
                    ->options(Supplier::all()->pluck('name', 'id'))
                    ->searchable(),
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Gudang')
                    ->options(Warehouse::all()->pluck('name', 'id'))
                    ->searchable()
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PurchaseReturnItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseReturns::route('/'),
            'create' => Pages\CreatePurchaseReturn::route('/create'),
            'edit' => Pages\EditPurchaseReturn::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', 'purchase_return');
    }
}
