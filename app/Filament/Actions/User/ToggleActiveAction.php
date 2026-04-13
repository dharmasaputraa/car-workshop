<?php

namespace App\Filament\Actions\User;

use App\Models\User;
use App\Services\User\UserService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ToggleActiveAction
{
    public static function make(): Action
    {
        return Action::make('toggle_active')
            ->label(fn(User $record): string => $record->is_active ? 'Deactivate' : 'Activate')
            ->icon(fn(User $record): Heroicon => $record->is_active ? Heroicon::XMark : Heroicon::Check)
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading(fn(User $record): string => ($record->is_active ? 'Deactivate' : 'Activate') . ' User?')
            ->modalDescription("Are you sure you want to change this user's access status?")
            ->visible(fn(User $record): bool => Auth::id() !== $record->id && Auth::user()->can('toggle_active_user'))
            ->authorize(fn(User $record): bool => Auth::id() !== $record->id && Auth::user()->can('toggle_active_user'))
            ->action(function (User $record, UserService $userService) {
                $updatedUser = $userService->toggleActive($record);

                Notification::make()
                    ->title($updatedUser->is_active ? 'User activated' : 'User deactivated')
                    ->success()
                    ->send();
            });
    }
}
