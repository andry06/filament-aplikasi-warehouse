<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Form;
use App\Models\Warehouse;
use Filament\Tables\Table;
use Illuminate\Support\Js;
use App\Models\Transaction;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use App\Services\TransactionService;
use Filament\Notifications\Notification;
use App\Services\ProductionReturnService;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Transaction\ProductionReturn;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProductionReturnResource\Pages;
use App\Filament\Resources\ProductionReturnResource\RelationManagers;

class ProductionReturnResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?string $modelLabel = 'Pengembalian Barang ke Gudang';
    protected static ?string $navigationLabel = 'Pengembalian Barang';

    protected static ?string $navigationGroup = 'Produksi';

    protected static ?int $navigationSort = 11;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Fieldset::make(fn ($livewire) => $livewire->record?->status == 'approve' ? 'Header - Approve ' : 'Header - Draft')
                ->schema([
                    Forms\Components\TextInput::make('number')
                        ->label('No Transaksi')
                        ->default(fn () => TransactionService::generateProductionAllocationNumber()['number'])
                        ->disabled()
                        ->required(),
                    Forms\Components\DatePicker::make('date')
                        ->label('Tanggal')
                        ->default(now())
                        ->required()
                        ->readOnly(fn ($livewire) => $livewire->record?->status == 'approve')
                        ->maxDate(today())
                        ->minDate(function () {
                            $transaction = Transaction::where('type', 'stock_opname')->orderBy('date', 'desc')->first();
                            return $transaction != null ? $transaction->date : null;
                        })
                        ->validationMessages([
                            'required' => 'Tanggal wajib diisi.',
                            'max_date' => 'Tanggal tidak boleh lebih besar dari hari ini.',
                        ]),
                    Forms\Components\Select::make('warehouse_id')
                        ->label('Gudang')
                        ->options(Warehouse::all()->pluck('name', 'id'))
                        ->disabled(fn ($livewire) => $livewire->record?->status == 'approve')
                        ->default(fn () => Warehouse::orderBy('id')->value('id')),
                    Forms\Components\TextInput::make('pic_field')
                        ->label('Dikirim oleh')
                        ->required()
                        ->readOnly(fn ($livewire) => $livewire->record?->status == 'approve')
                        ->validationMessages([
                            'required' => 'Dikirim oleh wajib diisi.',
                        ]),
                    Forms\Components\Select::make('project_id')
                        ->label('Nama Project')
                        ->relationship(
                            name: 'project',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query) => $query->where('has_allocation', true)->where('is_completed', false)->orderBy('name')
                        )
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->required(),
                        ])
                        ->createOptionModalHeading('Tambah Project Baru')
                        ->editOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->required(),
                        ])
                        ->editOptionModalHeading('Ubah Nama Project')
                        ->columnSpan(['xl' => 2])
                        ->searchable(['name'])
                        ->disabled(fn ($livewire) => $livewire->record != null)
                        ->validationMessages([
                            'required' => 'Nama Project wajib dipilih.',
                        ]),
                    Forms\Components\TextInput::make('note')
                        ->label('Catatan')
                        ->columnSpan(['xl' => 2]),
                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('create')
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
                            ->action(function ($livewire) {
                                try {
                                    $transactionService = app(TransactionService::class);
                                    if ($transactionService->isNotAllowedApprove($livewire->record)) {
                                        throw new \Exception('Transaksi ini terkunci karena sudah terdapat stock opname setelah tanggal transaksi ini.');
                                    }
                                    $productionReturnService = new ProductionReturnService();
                                    DB::beginTransaction();
                                    if ($livewire->record?->status == 'draft') {
                                        $productionReturnService->approve($livewire->record);
                                        $message = 'Status berhasil diapprove';
                                    } else {
                                        $productionReturnService->cancelApprove($livewire->record);
                                        $message = 'Status berhasil menjadi draft kembali';
                                    }
                                    DB::commit();
                                    Notification::make()
                                        ->title($message)
                                        ->success()
                                        ->send();
                                    return redirect()->route('filament.admin.resources.production-returns.edit', [
                                            'record' => $livewire->record->id,
                                        ]);
                                } catch (\Exception $e) {
                                    // info($e);
                                    DB::rollback();
                                    Notification::make()
                                        ->title('Gagal mengubah status')
                                        ->body($e->getMessage())
                                        ->warning()
                                        ->send();
                                }
                            }),
                        Forms\Components\Actions\Action::make('print')
                            ->label('Cetak')
                            ->color('primary')
                            ->extraAttributes([
                                'target' => '_blank'
                            ])
                            ->url(fn ($livewire) => route('print.production-returns', $livewire->record?->id))
                            ->visible(fn ($livewire) => $livewire->record != null),
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
                    ->url(fn($record) => ProductionReturnResource::getUrl('edit', ['record' => $record]))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->dateTime('d F Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('project_name')
                    ->label('Project')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pic_field')
                    ->label('Diterima oleh'),
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
            ->recordUrl(null)
            ->defaultSort('number', 'desc')
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProductionReturnItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductionReturns::route('/'),
            'create' => Pages\CreateProductionReturn::route('/create'),
            'edit' => Pages\EditProductionReturn::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', 'production_return');
    }

}
