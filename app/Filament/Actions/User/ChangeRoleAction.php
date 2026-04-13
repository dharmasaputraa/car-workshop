<?php

namespace App\Filament\Actions\User;

use App\Enums\RoleType;
use App\Models\User;
use App\Services\User\UserService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ChangeRoleAction
{
    public static function make(): Action
    {
        return Action::make('change_role')
            ->label('Change Role')
            ->icon('heroicon-o-shield-check')
            ->color('gray')
            ->visible(fn(User $record): bool => Auth::user()->can('change_role_user') && Auth::id() !== $record->id)
            ->authorize(fn(User $record): bool => Auth::user()->can('change_role_user') && Auth::id() !== $record->id)
            ->fillForm(fn(User $record): array => [
                'role' => $record->roles->first()?->name,
            ])
            ->schema([
                Select::make('role')
                    ->label('Select Role')
                    ->options(RoleType::class)
                    ->required()
                    ->native(false),
            ])
            ->action(function (User $record, array $data, UserService $userService): void {
                $userService->changeRole($record, $data['role']);

                Notification::make()
                    ->title('Role updated successfully')
                    ->success()
                    ->send();
            });
    }
}
