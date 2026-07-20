<?php

namespace App\Presentation\Http\Controllers\Web\Admin;

use App\Infrastructure\Persistence\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminVehicleController
{
    public function index(Request $request): View
    {
        $vehicles = Vehicle::with('service.provider')
            ->orderBy('ultima_consulta_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $totalVehicles = Vehicle::count();
        $sinRobo = Vehicle::where('ultimo_status_robo', false)->count();
        $conRobo = Vehicle::where('ultimo_status_robo', true)->count();

        return view('admin.vehicles.index', compact(
            'vehicles',
            'totalVehicles',
            'sinRobo',
            'conRobo'
        ));
    }
}
