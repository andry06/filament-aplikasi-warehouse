<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Filament\Resources\CompanyResource\RelationManagers;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompanyResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $modelLabel = 'Perusahaan';

    protected static ?string $recordTitleAttribute = 'company_name';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('company_name')
                    ->label('Nama')
                    ->maxLength(255)
                    ->required()
                    ->validationMessages([
                        'required' => 'Nama wajib diisi.',
                        'max' => 'Nama tidak boleh lebih dari :max karakter.',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('company_phone')
                    ->label('No Telp')
                    ->numeric()
                    ->maxLength(255)
                    ->prefix('+62')
                    ->validationMessages([
                        'max' => 'No Telp. tidak boleh lebih dari :max karakter.',
                    ]),
                Forms\Components\Textarea::make('company_address')
                    ->label('Alamat')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Nama')
                    // ->color('primary')
                    // ->weight('bold')
                    // ->action(Tables\Actions\EditAction::make())
                    // ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('company_address')
                    ->label('Alamat')
                    ->searchable(),
                Tables\Columns\TextColumn::make('company_phone')
                    ->label('No Telp.')
                    ->prefix('+62'),
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
            ->recordAction(null)
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCompanies::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return ! static::getModel()::count() > 1;
    }
}
