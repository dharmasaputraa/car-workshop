<?php

namespace App\Filament\Actions\User;

use App\Models\User;
use App\Services\User\UserService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class Disable2FAAction
{
    public static function make(): Action
    {
        return Action::make('disable_2fa')
            ->label('Disable 2FA')
            ->icon(Heroicon::LockOpen)
            ->color('gray')
            ->requiresConfirmation()
            ->visible(fn(User $record): bool => Auth::user()->can('disable2FA', $record))
            ->authorize(fn(User $record): bool => Auth::user()->can('disable2FA', $record))
            ->action(function (User $record, UserService $userService) {
                $userService->disableTwoFactorAuth($record);

                Notification::make()
                    ->title('2FA has been disabled')
                    ->success()
                    ->send();
            });
    }
}
