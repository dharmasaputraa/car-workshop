<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\RoleType;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // 🔷 LEFT SIDE (MAIN FORM)
                Group::make()
                    ->schema([

                        Tabs::make('User Form')
                            ->tabs([

                                // 🟦 BASIC INFO
                                Tab::make('Basic Info')
                                    ->schema([
                                        Section::make('Personal Information')
                                            ->schema([
                                                TextInput::make('name')
                                                    ->required()
                                                    ->maxLength(255),

                                                TextInput::make('email')
                                                    ->required()
                                                    ->email()
                                                    ->maxLength(255)
                                                    ->unique(User::class, 'email', ignoreRecord: true),
                                            ])
                                            ->columns(1),
                                    ]),

                                // 🟨 SECURITY
                                Tab::make('Security')
                                    ->schema([

                                        Section::make('Password')
                                            ->schema([
                                                TextInput::make('password')
                                                    ->password()
                                                    ->revealable()
                                                    ->required(fn($operation) => $operation === 'create')
                                                    ->dehydrated(fn($state) => filled($state))
                                                    ->suffixAction(
                                                        Action::make('generatePassword')
                                                            ->icon('heroicon-m-arrow-path')
                                                            ->tooltip('Generate Password')
                                                            ->action(fn($set) => $set('password', Str::random(10)))
                                                    )
                                                    ->helperText('Leave blank if not changing'),
                                            ]),

                                        Section::make('Email Verification')
                                            ->schema([

                                                Toggle::make('email_verified_toggle')
                                                    ->label('Verified')
                                                    ->inline()
                                                    ->onIcon(Heroicon::Check)
                                                    ->offIcon(Heroicon::XMark)
                                                    ->reactive()
                                                    ->afterStateHydrated(function ($set, $get) {
                                                        $set('email_verified_toggle', filled($get('email_verified_at')));
                                                    })
                                                    ->afterStateUpdated(function ($state, $set) {
                                                        $set('email_verified_at', $state ? now() : null);
                                                    }),

                                                DateTimePicker::make('email_verified_at')
                                                    ->label('Verified At')
                                                    ->native(false)
                                                    ->visible(fn($get) => $get('email_verified_toggle')),
                                            ])
                                            ->columns(1),
                                    ]),

                                // 🟥 ACCESS & STATUS
                                Tab::make('Access & Status')
                                    ->schema([
                                        Section::make('Access Control')
                                            ->schema([

                                                Select::make('role')
                                                    ->options(
                                                        collect(RoleType::cases())
                                                            ->mapWithKeys(fn($case) => [
                                                                $case->value => $case->getLabel()
                                                            ])
                                                    )
                                                    ->native(false)
                                                    ->default(RoleType::MEMBER->value)
                                                    ->required()
                                                    ->helperText('Default role is Member'),

                                                Toggle::make('is_active')
                                                    ->label('Active')
                                                    ->inline()
                                                    ->onIcon(Heroicon::Check)
                                                    ->offIcon(Heroicon::XMark)
                                                    ->default(true)
                                                    ->helperText('Inactive users cannot login'),
                                            ])
                                            ->columns(1),
                                    ]),
                            ]),

                    ])
                    ->columnSpan(['lg' => 2]),

                // 🔷 RIGHT SIDE (SIDEBAR)
                Group::make()
                    ->schema([

                        Section::make('Avatar')
                            ->schema([
                                FileUpload::make('avatar_url')
                                    ->label('Avatar')
                                    ->hiddenLabel(true)
                                    ->disk('s3')
                                    ->directory('avatars')
                                    ->image()
                                    ->imageEditor()
                                    ->maxSize(2048)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->automaticallyCropImagesToAspectRatio('1:1')
                                    ->automaticallyResizeImagesToWidth(800)
                                    ->automaticallyResizeImagesToHeight(800)
                            ]),

                    ])
                    ->columnSpan(['lg' => 1]),

            ])
            ->columns(3);
    }
}
