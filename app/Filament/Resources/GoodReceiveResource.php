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
use App\Services\GoodReceiveService;
use App\Traits\TransactionNumberTrait;
use Filament\Forms\Components\Actions;
use App\Models\Transaction\GoodReceive;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Services\TransactionNumberService;
use Filament\Forms\Components\Actions\Action;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\GoodReceiveResource\Pages;
use Filament\Resources\RelationManagers\RelationGroup;
use App\Filament\Resources\GoodReceiveResource\RelationManagers;

class GoodReceiveResource extends Resource
{

    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $modelLabel = 'Penerimaan Barang';

    protected static ?string $navigationGroup = 'Pembelian';

    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Forms\Components\Fieldset::make(fn ($livewire) => $livewire->record?->status == 'approve' ? 'Header - Approve ' : 'Header - Draft')
                ->schema([
                    Forms\Components\DatePicker::make('date')
                        ->default(now())
                        ->required()
                        ->readOnly(fn ($livewire) => $livewire->record?->status == 'approve'),
                    Forms\Components\TextInput::make('number')
                        ->label('No Transaksi')
                        ->default(fn () => TransactionNumberService::generateGoodReceiveNumber()['number'])
                        ->disabled()
                        ->required(),
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
                        ->label('No SJ / Invoice')
                        ->required()
                        ->readOnly(fn ($livewire) => $livewire->record?->status == 'approve')
                        ->validationMessages([
                            'required' => 'No SJ / Invoice wajib diisi.',
                        ]),
                    Forms\Components\TextInput::make('received_by')
                        ->label('Diterima oleh')
                        ->required()
                        ->readOnly(fn ($livewire) => $livewire->record?->status == 'approve')
                        ->validationMessages([
                            'required' => 'Diterima oleh wajib diisi.',
                        ]),
                    Forms\Components\TextInput::make('note')
                        ->label('Catatan'),
                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('Buat')
                            ->label('Buat')
                            ->submit('create')
                            ->color('primary')
                            ->visible(fn () => request()->routeIs('filament.admin.resources.good-receives.create')),
                        Forms\Components\Actions\Action::make('save')
                            ->label('Simpan')
                            ->submit('save')
                            ->visible(fn ($livewire) => $livewire->record?->status == 'draft')
                            ->color('primary'),
                        Forms\Components\Actions\Action::make('approve')
                            ->label('Approve')
                            ->color('success')
                            ->requiresConfirmation()
                            ->action(function ($livewire) {
                                try {
                                    app(GoodReceiveService::class)->approve($livewire->record);

                                    Notification::make()
                                        ->title('Status berhasil dibatalkan')
                                        ->success()
                                        ->send();
                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->title('Gagal membatalkan status')
                                        ->body($e->getMessage())
                                        ->warning()
                                        ->send();
                                }
                            })
                            ->visible(fn ($livewire) => $livewire->record?->status == 'draft'),
                        Forms\Components\Actions\Action::make('draft')
                            ->label('Cancel Approve')
                            ->requiresConfirmation()
                            ->color('warning')
                            ->action(function ($livewire) {
                                try {
                                    app(GoodReceiveService::class)->cancelApprove($livewire->record);

                                    Notification::make()
                                        ->title('Status berhasil dibatalkan')
                                        ->success()
                                        ->send();
                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->title('Gagal membatalkan status')
                                        ->body($e->getMessage())
                                        ->warning()
                                        ->send();
                                }
                            })
                            ->visible(fn ($livewire) => $livewire->record?->status == 'approve'),
                        Forms\Components\Actions\Action::make('cancel')
                            ->label('Batal')
                            ->alpineClickHandler(
                                "document.referrer ? window.history.back() : window.location.href = " . Js::from(
                                    url()->previous() ?? static::getResource()::getUrl()
                                )
                            )
                            ->visible(fn () => request()->routeIs('filament.admin.resources.good-receives.create'))
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
                    ->url(fn($record) => GoodReceiveResource::getUrl('edit', ['record' => $record]))
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
                Tables\Columns\TextColumn::make('received_by')
                    ->label('Diterima oleh'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'warning',
                        'approve' => 'success',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),
            ])
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
            RelationGroup::make('Detail Barang', [
                RelationManagers\GoodReceiveItemsRelationManager::class,
            ])->icon('heroicon-m-cog-6-tooth')->iconPosition('after')
            // RelationGroup::make('Item Colors', [
            //     RelationManagers\ItemColorsRelationManager::class,
            // ]),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGoodReceives::route('/'),
            'create' => Pages\CreateGoodReceive::route('/create'),
            'edit' => Pages\EditGoodReceive::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', 'purchase_in');
    }
}

