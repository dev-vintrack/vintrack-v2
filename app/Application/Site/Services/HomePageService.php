<?php

namespace App\Application\Site\Services;

use App\Infrastructure\Persistence\Models\Vehicle;
use App\Models\User;

class HomePageService
{
    public function stats(): array
    {
        return [
            'vehicles' => Vehicle::count(),
            'active_users' => User::where('activo', true)
                ->where('status', 'active')
                ->whereHas('role.roleType', function ($query) {
                    $query->where('is_customer', true);
                })
                ->count(),
        ];
    }
}
