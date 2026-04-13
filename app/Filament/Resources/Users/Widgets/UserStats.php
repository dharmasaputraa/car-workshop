<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Enums\RoleType;
use App\Filament\Resources\Users\Pages\ListUsers;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStats extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected ?string $pollingInterval = null;

    protected function getTablePage(): string
    {
        return ListUsers::class;
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery();

        $totalUsers = $query->count();
        $activeUsers = (clone $query)->where('is_active', true)->count();

        // Filter by Spatie Role
        $totalMembers = (clone $query)
            ->whereHas('roles', fn($q) => $q->where('name', RoleType::MEMBER->value))
            ->count();

        return [
            Stat::make('Total Users', $totalUsers),

            Stat::make('Total Members', $totalMembers)
                ->color('info'),

            Stat::make('Active Users', $activeUsers)
                ->color('success'),


        ];
    }
}
