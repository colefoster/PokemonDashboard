<?php

namespace App\Filament\Resources\Abilities;

use App\Filament\Resources\Abilities\Pages\CreateAbility;
use App\Filament\Resources\Abilities\Pages\EditAbility;
use App\Filament\Resources\Abilities\Pages\ListAbilities;
use App\Filament\Resources\Abilities\Pages\ViewAbility;
use App\Filament\Resources\Abilities\Schemas\AbilityForm;
use App\Filament\Resources\Abilities\Schemas\AbilityInfolist;
use App\Filament\Resources\Abilities\Tables\AbilitiesTable;
use App\Models\Ability;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AbilityResource extends Resource
{
    protected static ?string $model = Ability::class;
    protected static string|null|\UnitEnum $navigationGroup = 'Data';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return AbilityForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AbilityInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AbilitiesTable::configure($table);
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
            'index' => ListAbilities::route('/'),
            'create' => CreateAbility::route('/create'),
            'view' => ViewAbility::route('/{record}'),
            'edit' => EditAbility::route('/{record}/edit'),
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
