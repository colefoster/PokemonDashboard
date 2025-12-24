<?php

namespace App\Filament\Resources\Pokemon;

use App\Filament\Resources\Pokemon\Pages\CreatePokemon;
use App\Filament\Resources\Pokemon\Pages\EditPokemon;
use App\Filament\Resources\Pokemon\Pages\ListPokemon;
use App\Filament\Resources\Pokemon\Pages\ViewPokemon;
use App\Filament\Resources\Pokemon\Schemas\PokemonForm;
use App\Filament\Resources\Pokemon\Schemas\PokemonInfolist;
use App\Filament\Resources\Pokemon\Tables\PokemonTable;
use App\Models\Pokemon;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PokemonResource extends Resource
{
    protected static ?string $model = Pokemon::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Data';
    protected static ?int $navigationSort = -2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return PokemonForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PokemonInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PokemonTable::configure($table);
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
            'index' => ListPokemon::route('/'),
            'create' => CreatePokemon::route('/create'),
            'view' => ViewPokemon::route('/{record}'),
            'edit' => EditPokemon::route('/{record}/edit'),
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
