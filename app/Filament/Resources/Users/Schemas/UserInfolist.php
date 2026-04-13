<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\RoleType;
use App\Models\User;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Gunakan columnSpanFull() pada Grid paling luar
                Grid::make([
                    'default' => 1,
                    'lg' => 3,
                ])
                    ->schema([

                        // Kiri: User Profile (Main Content)
                        Group::make([
                            Section::make('User Profile')
                                ->description('Account identification')
                                ->schema([
                                    ImageEntry::make('avatar_url')
                                        ->alignCenter()
                                        ->hiddenLabel()
                                        ->circular()
                                        ->grow(false)
                                        ->disk('s3')
                                        ->defaultImageUrl(fn(User $record) => 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($record->email))) . '?s=200&d=mp'),
                                    Flex::make([

                                        Grid::make(1)
                                            ->schema([
                                                Group::make([
                                                    TextEntry::make('name'),
                                                    TextEntry::make('email')
                                                        ->icon('heroicon-m-envelope')
                                                        ->copyable()
                                                        ->color('gray'),
                                                    // TextEntry::make('roles.name')
                                                    //     ->label('Roles')
                                                    //     ->badge()
                                                    //     ->color('primary')
                                                    //     ->formatStateUsing(
                                                    //         fn(string $state): string =>
                                                    //         RoleType::tryFrom($state)?->getLabel() ?? $state
                                                    //     ),
                                                ]),
                                                // Group::make([
                                                //     IconEntry::make('is_active')
                                                //         ->label('Account Status')
                                                //         ->boolean(),
                                                //     TextEntry::make('email_verified_at')
                                                //         ->label('Verified')
                                                //         ->getStateUsing(fn($record) => $record->email_verified_at ? 'Verified' : 'Not Verified')
                                                //         ->badge()
                                                //         ->color(fn($state) => $state === 'Verified' ? 'success' : 'gray')
                                                //         ->tooltip(fn($record) => $record->email_verified_at?->format('d M Y H:i'))
                                                // ]),
                                            ]),


                                    ])->from('lg'),
                                ]),
                        ])
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 2,
                            ]),

                        // Kanan: Sidebar
                        Group::make([
                            Section::make('Access & Status')
                                ->schema([
                                    TextEntry::make('roles.name')
                                        ->label('Roles')
                                        ->badge()
                                        ->color('primary')
                                        ->formatStateUsing(
                                            fn(string $state): string =>
                                            RoleType::tryFrom($state)?->getLabel() ?? $state
                                        ),
                                    IconEntry::make('is_active')
                                        ->label('Account Status')
                                        ->boolean(),

                                ]),
                            Section::make('Security')
                                ->schema([
                                    TextEntry::make('email_verified_at')
                                        ->label('Verified')
                                        ->getStateUsing(fn($record) => $record->email_verified_at ? 'Verified' : 'Not Verified')
                                        ->badge()
                                        ->color(fn($state) => $state === 'Verified' ? 'success' : 'gray')
                                        ->tooltip(fn($record) => $record->email_verified_at?->format('d M Y H:i')),
                                    TextEntry::make('two_factor_confirmed_at')
                                        ->label('2FA')
                                        ->getStateUsing(fn(User $record) => $record->hasConfirmedTwoFactor() ? 'Enabled' : 'Disabled')
                                        ->badge()
                                        ->color(fn(string $state): string => $state === 'Enabled' ? 'success' : 'gray')
                                        ->tooltip(fn(User $record) => $record->breezySession?->created_at?->format('d M Y H:i'))
                                        ->placeholder('Not enabled'),

                                ]),

                            Section::make('Timestamps')
                                ->schema([
                                    TextEntry::make('created_at')
                                        ->label('Created')
                                        ->dateTime()
                                        ->size('sm'),
                                    TextEntry::make('updated_at')
                                        ->label('Updated')
                                        ->dateTime()
                                        ->size('sm'),
                                    TextEntry::make('deleted_at')
                                        ->label('Deleted')
                                        ->dateTime()
                                        ->color('danger')
                                        ->visible(fn(User $record): bool => $record->trashed()),
                                ]),
                        ])
                            ->columnSpan(1),

                    ])
                    ->columnSpanFull(), // <--- TAMBAHKAN INI agar memenuhi lebar layar
            ]);
    }
}
