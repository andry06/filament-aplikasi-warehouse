<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Warehouse;
use Filament\Tables\Table;
use Illuminate\Support\Js;
use App\Models\Transaction;
use Filament\Resources\Resource;
use App\Services\TransactionService;
use App\Models\Transaction\StockOpname;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\StockOpnameResource\Pages;
use App\Filament\Resources\StockOpnameResource\RelationManagers;

class StockOpnameResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $modelLabel = 'Stock Opname';
    protected static ?string $navigationLabel = 'Stock Opname';

    protected static ?string $navigationGroup = 'Manajemen Stok';

    protected static ?int $navigationSort = 12;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Fieldset::make(fn ($livewire) => $livewire->record?->status == 'approve' ? 'Header - Approve ' : 'Header - Draft')
                ->schema([
                    Forms\Components\TextInput::make('number')
                        ->label('No Transaksi')
                        ->default(fn () => TransactionService::generateStockOpnameNumber()['number'])
                        ->disabled()
                        ->required(),
                    Forms\Components\DatePicker::make('date')
                        ->label('Tanggal')
                        ->default(now())
                        ->required()
                        ->readOnly(fn ($livewire) => $livewire->record?->status == 'approve')
                        ->maxDate(today())
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
                        ->label('PIC Pelaksana')
                        ->required()
                        ->readOnly(fn ($livewire) => $livewire->record?->status == 'approve')
                        ->validationMessages([
                            'required' => 'PIC Pelaksana oleh wajib diisi.',
                        ]),
                    Forms\Components\TextInput::make('note')
                        ->label('Catatan')
                        ->columnSpanFull(),
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
                                // try {
                                //     DB::beginTransaction();
                                //     $productionAllocationService = app(ProductionAllocationService::class);

                                //     if ($livewire->record?->status == 'draft') {
                                //         $productionAllocationService->approve($livewire->record);
                                //         $message = 'Status berhasil diapprove';
                                //     } else {

                                //         $productionAllocationService->cancelApprove($livewire->record);

                                //         $message = 'Status berhasil menjadi draft kembali';
                                //     }

                                //     DB::commit();

                                //     Notification::make()
                                //         ->title($message)
                                //         ->success()
                                //         ->send();

                                //     return redirect()->route('filament.admin.resources.production-allocations.edit', [
                                //             'record' => $livewire->record->id,
                                //         ]);
                                // } catch (\Exception $e) {
                                //     info($e);
                                //     DB::rollback();
                                //     Notification::make()
                                //         ->title('Gagal mengubah status')
                                //         ->body($e->getMessage())
                                //         ->warning()
                                //         ->send();
                                // }
                            }),
                        Forms\Components\Actions\Action::make('print')
                            ->label('Cetak')
                            ->color('primary')
                            ->extraAttributes([
                                'target' => '_blank'
                            ])
                            ->url(fn ($livewire) => route('print.production-allocations', $livewire->record?->id))
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
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            RelationManagers\StockOpnameItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockOpnames::route('/'),
            'create' => Pages\CreateStockOpname::route('/create'),
            'edit' => Pages\EditStockOpname::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', 'stock_opname');
    }
}
