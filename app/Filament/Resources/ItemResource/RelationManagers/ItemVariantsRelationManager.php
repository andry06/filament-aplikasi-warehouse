<?php

namespace App\Filament\Resources\ItemResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ItemVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Validation\Rules\Unique as UniqueRule;


class ItemVariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'ItemVariants';

    protected static ?string $title = 'Varian Barang';

    public static function getModelLabel(): string
    {
        return 'Varian Barang';
    }


    public function form(Form $form): Form
    {
        $itemId = $this->ownerRecord->id;

        return $form
            ->schema([
                Forms\Components\TextInput::make('color')
                    ->label('Warna')
                    ->required()
                    ->columnSpanFull()
                    ->maxLength(255)
                    ->unique(
                        table: ItemVariant::class,
                        column: 'color',
                        ignoreRecord: true,
                        modifyRuleUsing: function (UniqueRule $rule, string $context, $record) use ($itemId) {
                            // scoping by item_id
                            return $rule->where('item_id', $itemId);
                        },
                    )
                    ->validationMessages([
                        'required' => 'Warna wajib diisi.',
                        'unique' => 'Warna sudah digunakan, silakan gunakan nama lain.',
                        'max' => 'Warna tidak boleh lebih dari :max karakter.',
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('color')
            ->columns([
                Tables\Columns\TextColumn::make('color')
                    ->label('Warna')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d F Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d F Y H:i')
                    ->sortable(),
            ])
            ->recordAction(null)
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->modalWidth('md'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('')->iconSize('md')->tooltip('Edit')->modalWidth('md'),
                Tables\Actions\DeleteAction::make()->label('')->tooltip('Hapus')->iconSize('md')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

}
