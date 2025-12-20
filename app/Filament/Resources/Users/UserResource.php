<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;


class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-users';

    public static function getNavigationBadge():?string
    {
        return static::getModel()::count();
    }



    protected static string|null|\UnitEnum $navigationGroup = 'Administration';

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'The number of users';
    }


    public static function form(Schema $schema): Schema
    {
        //return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->is_admin ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
