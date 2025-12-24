<?php

namespace App\Filament\Resources\Moves;

use App\Filament\Resources\Moves\Pages\CreateMove;
use App\Filament\Resources\Moves\Pages\EditMove;
use App\Filament\Resources\Moves\Pages\ListMoves;
use App\Filament\Resources\Moves\Pages\ViewMove;
use App\Filament\Resources\Moves\Schemas\MoveForm;
use App\Filament\Resources\Moves\Schemas\MoveInfolist;
use App\Filament\Resources\Moves\Tables\MovesTable;
use App\Models\Move;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MoveResource extends Resource
{
    protected static ?string $model = Move::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|null|\UnitEnum $navigationGroup = 'Data';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return MoveForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MoveInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        $movesTable = MovesTable::configure($table);
        return $movesTable;
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
            'index' => ListMoves::route('/'),
            'create' => CreateMove::route('/create'),
            'view' => ViewMove::route('/{record}'),
            'edit' => EditMove::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
