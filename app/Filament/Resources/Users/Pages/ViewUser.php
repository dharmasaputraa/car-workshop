<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Actions\User\ChangeRoleAction;
use App\Filament\Actions\User\Disable2FAAction;
use App\Filament\Actions\User\ToggleActiveAction;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn() => ! $this->record->trashed()),

            RestoreAction::make()
                ->icon(Heroicon::ArrowUturnLeft),

            ActionGroup::make([
                ChangeRoleAction::make($this),
                ToggleActiveAction::make($this),

                ActionGroup::make([
                    Disable2FAAction::make($this),
                ])->dropdown(false),

                ActionGroup::make([
                    DeleteAction::make()
                        ->icon(Heroicon::Trash),
                ])->dropdown(false),

            ])
                ->hiddenLabel()
                ->icon(Heroicon::EllipsisVertical)
                ->color('gray')
                ->button()
                ->visible(fn() => ! $this->record->trashed()),
        ];
    }
}
