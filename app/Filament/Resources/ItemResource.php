<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Item;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ItemResource\Pages;
use Filament\Resources\Pages\ContentTabPosition;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationGroup;
use App\Filament\Resources\ItemResource\RelationManagers;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $modelLabel = 'Barang';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 18;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Radio::make('category')
                    ->label('Kategori')
                    ->options([
                        'assets' => 'Aset',
                        'main_material' => 'Material Utama',
                        'accessories' => 'Aksesoris',
                    ])
                    ->inline()
                    ->inlineLabel(false)
                    ->required(),
                Forms\Components\TextInput::make('code')
                    ->label('Kode')
                    ->required()
                    ->validationMessages([
                        'required' => 'Kode wajib diisi.',
                        'max' => 'Kode tidak boleh lebih dari :max karakter.',
                    ])
                    ->maxLength(255),
                Forms\Components\TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->unique(
                        table: 'items',
                        column: 'name',
                        ignorable: fn ($record) => $record
                    )
                    ->validationMessages([
                        'required' => 'Nama wajib diisi.',
                        'unique' => 'Nama sudah digunakan, silakan gunakan nama lain.',
                        'max' => 'Nama tidak boleh lebih dari :max karakter.',
                    ])
                    ->maxLength(255),
                Forms\Components\TextInput::make('unit')
                    ->label('Satuan')
                    ->required()
                    ->validationMessages([
                        'required' => 'Satuan wajib diisi.',
                        'max' => 'Satuan tidak boleh lebih dari :max karakter.',
                    ])
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    // ->color('primary')
                    // ->weight('bold')
                    // ->wrap()
                    // ->url(fn($record) => ItemResource::getUrl('edit', ['record' => $record]))
                    ->searchable(),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Satuan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Jenis Kelamin') // Mengubah label kolom
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'aset' => 'Aset',
                        'main_material' => 'Material Utama',
                        'aksesoris' => 'Aksesoris',
                        default => ucfirst($state), // Jika ada nilai lain
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $search = strtolower($search);

                        return $query->whereRaw("
                            CASE
                                WHEN category = 'assets' THEN 'assets'
                                WHEN category = 'main_material' THEN 'material utama'
                                WHEN category = 'accessories' THEN 'aksesoris'
                                ELSE gender
                            END LIKE ?
                        ", ["%$search%"]);
                    })
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
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordUrl(null)
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('')->tooltip('Edit')->iconSize('md'),
                Tables\Actions\DeleteAction::make()->label('')->tooltip('Hapus')->iconSize('md'),
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
            RelationManagers\ItemVariantsRelationManager::class,
            // RelationGroup::make('Detail Barang', [
            //     RelationManagers\ItemDetailsRelationManager::class,
            // ])->icon('heroicon-m-cog-6-tooth')->iconPosition('after')->badge('new')            ,
            // RelationGroup::make('Item Colors', [
            //     RelationManagers\ItemColorsRelationManager::class,
            // ]),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListItems::route('/'),
            'create' => Pages\CreateItem::route('/create'),
            'edit' => Pages\EditItem::route('/{record}/edit'),
        ];
    }




}
