<?php

namespace App\Filament\Resources\GoodReceiveResource\RelationManagers;

use Filament\Forms;
use App\Models\Item;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class GoodReceiveItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactionDetails';

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
                    // Kolom kiri (avatar)
                    Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Radio::make('category')
                        ->label('Kategori')
                        ->options([
                            'assets' => 'Aset',
                            'main_material' => 'Material Utama',
                            'accessories' => 'Aksesoris',
                        ])
                        // ->inline()
                        // ->inlineLabel(false)
                        ->required(),
                    ])
                    ->columnSpan(2),
                    // ->extraAttributes([
                    //     'class' => 'flex flex-col items-center justify-center',
                    //     'style' => 'min-height: 100%;'
                    // ]),
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Select::make('item_id')
                                ->label('Barang')
                                ->options(Item::where('category', 'main_material')->pluck('name', 'id'))
                                ->searchable(),
                            Forms\Components\Select::make('item_id')
                                ->label('Warna')
                                ->options(Item::where('category', 'main_material')->pluck('name', 'id'))
                                ->searchable(),

                            Forms\Components\TextInput::make('price')
                                ->label('Harga')
                                ->required()
                                ->validationMessages([
                                    'required' => 'Diterima oleh wajib diisi.',
                                ]),
                            Forms\Components\TextInput::make('qty')
                                ->label('Jumlah')
                                ->required()
                                ->validationMessages([
                                    'required' => 'Diterima oleh wajib diisi.',
                                ]),
                        ])->columnSpan(10)->columns(2),
                    Forms\Components\TextInput::make('note')
                        ->label('Catatan')
                        ->columnSpanFull()
                        ->maxLength(255)
                        ->required()
                        ->validationMessages([
                            'max' => 'Nama tidak boleh lebih dari :max karakter.',
                            'required' => 'Diterima oleh wajib diisi.',
                        ]),
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
                Tables\Columns\TextColumn::make('item_detail_id'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->modalWidth('5xl')
                    ->modalHeading('Tambah Barang yang diterima')
                    ->label('Tambah Barang'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }


}
