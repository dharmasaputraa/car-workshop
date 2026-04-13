<?php

namespace App\Filament\Actions\User;

use App\Models\User;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use STS\FilamentImpersonate\Actions\Impersonate;

class ImpersonateUserAction
{
    public static function make(): Action
    {
        return Impersonate::make()
            ->label('')
            ->color('gray')
            ->tooltip('Impersonate')
            ->requiresConfirmation()
            ->modalHeading('Impersonate User')
            ->modalDescription('Are you sure you want to log in as this user?')
            ->modalSubmitActionLabel('Yes, continue')
            ->visible(
                fn(User $record): bool =>
                Auth::user()->canImpersonate() &&
                    $record->canBeImpersonated() &&
                    Auth::id() !== $record->id
            )
            ->redirectTo(function (User $record): string {
                if ($record->isAdmin() || $record->isSuperAdmin()) {
                    return route('filament.admin.pages.dashboard');
                }

                return route('home');
            });
    }
}
