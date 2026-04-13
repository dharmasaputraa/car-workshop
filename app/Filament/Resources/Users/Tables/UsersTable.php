<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\RoleType;
use App\Filament\Actions\User\ChangeRoleAction;
use App\Filament\Actions\User\Disable2FAAction;
use App\Filament\Actions\User\ImpersonateUserAction;
use App\Filament\Actions\User\ToggleActiveAction;
use App\Models\User;
use App\Services\User\UserService;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->state(fn(User $record) => $record->getFilamentAvatarUrl())
                    ->disk('s3'),
                TextColumn::make('name')
                    ->searchable()
                    ->weight(FontWeight::Medium),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => RoleType::tryFrom($state)?->getLabel() ?? $state)
                    ->color(fn(string $state): string => RoleType::tryFrom($state)?->getColor() ?? 'gray')
                    ->separator(',')
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->label("Email Verification")
                    ->sortable()
                    ->state(fn($record) => $record->email_verified_at ? 'Verified' : 'Not Verified')
                    ->badge()
                    ->color(fn($state) => $state === 'Verified' ? 'success' : 'gray')
                    ->tooltip(fn($record) => $record->email_verified_at?->format('d M Y H:i'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('breezySession.is_confirmed') // Mengakses kolom di tabel breezy_sessions
                    ->label('2FA Status')
                    ->state(fn(User $record) => $record->hasConfirmedTwoFactor() ? 'Enabled' : 'Disabled')
                    ->badge()
                    ->color(fn(string $state): string => $state === 'Enabled' ? 'success' : 'gray')
                    ->tooltip(fn(User $record) => $record->breezySession?->created_at?->format('d M Y H:i'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn($record) => RoleType::tryFrom($record->name)?->getLabel() ?? $record->name)
                    ->multiple()
                    ->preload(),

                TernaryFilter::make('email_verified_at')
                    ->label('Email Verification')
                    ->nullable()
                    ->placeholder('All Users')
                    ->trueLabel('Verified Only')
                    ->falseLabel('Not Verified Only')
                    ->native(false),

                TernaryFilter::make('two_factor_status')
                    ->label('2FA Status')
                    ->placeholder('All Users')
                    ->trueLabel('2FA Enabled')
                    ->falseLabel('2FA Disabled')
                    ->queries(
                        true: fn(Builder $query) => $query->whereHas('breezySession', fn($q) => $q->whereNotNull('confirmed_at')),
                        false: fn(Builder $query) => $query->whereDoesntHave('breezySession')->orWhereHas('breezySession', fn($q) => $q->whereNull('confirmed_at')),
                    )
                    ->native(false),

                TrashedFilter::make()
                    ->native(false),
            ])
            ->recordActions([
                ImpersonateUserAction::make(),

                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->color("gray"),

                    ActionGroup::make([

                        ToggleActiveAction::make(),
                        ChangeRoleAction::make(),
                        Disable2FAAction::make(),

                    ])->dropdown(false),

                    ActionGroup::make([
                        RestoreAction::make(),
                        DeleteAction::make()
                            ->visible(
                                fn(User $record): bool =>
                                Auth::user()->can('delete_user') && Auth::id() !== $record->id
                            ),
                    ])->dropdown(false),
                ])->color('gray'),

            ])
            ->checkIfRecordIsSelectableUsing(
                fn(User $record): bool =>
                $record->id !== Auth::id()
            )
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('toggle_active')
                        ->icon(Heroicon::Power)
                        ->color('warning')
                        ->schema([
                            ToggleButtons::make('is_active')
                                ->options([
                                    '1' => 'Active',
                                    '0' => 'Inactive',
                                ])
                                ->inline()
                                ->required(),
                        ])
                        ->visible(
                            fn(): bool =>
                            Auth::user()->can('toggle_active_user')
                        )
                        ->action(function (\Illuminate\Support\Collection $records, array $data, UserService $userService): void {
                            $isActive = (bool) $data['is_active'];

                            $records
                                ->filter(fn(User $record) => $record->id !== Auth::id())
                                ->each(fn(User $record) => $userService->updateUser($record, ['is_active' => $isActive]));

                            Notification::make()
                                ->title('Selected users updated')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
