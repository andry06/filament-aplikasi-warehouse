<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\UserResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UserResource\RelationManagers;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'Pengguna';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 19;

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Forms\Components\Grid::make(12)
                ->schema([
                    // Kolom kiri (avatar)
                    Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\FileUpload::make('avatar')
                            ->label('Avatar')
                            ->hiddenLabel()
                            ->image()
                            ->avatar()
                            ->imageEditor(1)
                            ->circleCropper()
                            ->alignment('center'),

                        Forms\Components\Placeholder::make('')
                            ->content('Unggah foto profil dengan rasio 1:1 untuk hasil terbaik.')
                            ->extraAttributes(['class' => 'text-center text-sm text-gray-500']),
                    ])
                    ->columnSpan(4)
                    ->extraAttributes([
                        'class' => 'flex flex-col items-center justify-center',
                        'style' => 'min-height: 100%;'
                    ]),
                    // Kolom kanan (nama, email, password)
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Nama')
                                ->required(),
                            Forms\Components\Radio::make('gender')
                                ->label('Jenis Kelamin')
                                ->options([
                                    'male' => 'Laki-laki',
                                    'female' => 'Perempuan',
                                ])
                                ->inline()
                                ->inlineLabel(false)
                                ->required(),
                            Forms\Components\TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->required(),
                            Forms\Components\TextInput::make('password')
                                ->label('Password')
                                ->password()
                                ->required(fn (string $context): bool => $context === 'create')
                                ->visible(fn () => in_array(Auth::id(), [1, 2]))
                                ->dehydrated(fn ($state) => filled($state)),
                        ])
                        ->columnSpan(8),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ViewColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->view('components.tables.users.name-column'),
                Tables\Columns\TextColumn::make('gender')
                    ->label('Jenis Kelamin') // Mengubah label kolom
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'male' => 'Laki-laki',
                        'female' => 'Perempuan',
                        default => ucfirst($state), // Jika ada nilai lain
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $search = strtolower($search);

                        return $query->whereRaw("
                            CASE
                                WHEN gender = 'male' THEN 'laki-laki'
                                WHEN gender = 'female' THEN 'perempuan'
                                ELSE gender
                            END LIKE ?
                        ", ["%$search%"]);
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
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
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Edit')
                    ->iconSize('md')
                    ->visible(fn () => in_array(Auth::id(), [1, 2])),
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Hapus')
                    ->iconSize('md')
                    ->visible(fn () => in_array(Auth::id(), [1, 2])),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
