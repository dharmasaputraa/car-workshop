<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\RoleType;
use App\Filament\Resources\Users\UserResource;
use App\Services\User\UserService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['role'] = $this->record->roles->first()?->name;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $userService = app(UserService::class);
        $user = $userService->updateUser($record, $data);

        if (isset($data['role'])) {
            $userService->changeRole($user, $data['role']);
        }

        return $user;
    }
}
