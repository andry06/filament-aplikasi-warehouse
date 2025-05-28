<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Project;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProjectResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Filament\Resources\ProjectResource\RelationManagers\ProjectTransactionsRelationManager;
use Guava\FilamentModalRelationManagers\Actions\Table\RelationManagerAction;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-square-3-stack-3d';

    protected static ?string $modelLabel = 'Project Produksi';
    protected static ?string $navigationLabel = 'Project';

    protected static ?string $navigationGroup = 'Produksi';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                        ->label('Nama Project')
                        ->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Project')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal Mulai')
                    ->dateTime('d F Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('material_cost')
                    ->label('Total Biaya Material')
                    ->formatStateUsing(fn ($state) => rupiah($state))
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_completed')
                    ->label('Status Selesai')
                    ->alignCenter(),
            ])
            ->filters([
                //
            ])
            ->actions([
                RelationManagerAction::make('project-transactions-relation-manager')
                    ->label('Detail')
                    ->icon('heroicon-o-magnifying-glass-plus')
                    ->hiddenLabel()
                    ->iconSize('md')
                    ->modalWidth('6xl')
                    ->modalHeading('Detail Pemakaian Material')
                    ->closeModalByClickingAway(false)
                ->relationManager(ProjectTransactionsRelationManager::make()),
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
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
