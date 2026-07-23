<?php

namespace App\Presentation\Http\Controllers\Web\Admin;

use App\Application\Credits\CommandHandlers\AddCreditsCommandHandler;
use App\Application\Credits\Commands\AddCreditsCommand;
use App\Infrastructure\Persistence\Models\CreditPackage;
use App\Infrastructure\Persistence\Models\CreditPackageItem;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Infrastructure\Persistence\Models\UserPackage;
use App\Models\User;
use App\Presentation\Support\RoleHelper;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminPackageController
{
    public function __construct(
        private readonly AddCreditsCommandHandler $addCreditsHandler
    ) {
    }

    public function index()
    {
        $packages = CreditPackage::with('items.service')->orderBy('name')->get();

        return view('admin.packages.index', compact('packages'));
    }

    private function allowedServices()
    {
        return ProviderService::with('provider')
            ->where('enabled', true)
            ->whereIn('id', RoleHelper::allowedServiceIds(Auth::user()?->id_rol))
            ->orderBy('provider_id')
            ->orderBy('name')
            ->get();
    }

    public function create()
    {
        return view('admin.packages.create', ['services' => $this->allowedServices()]);
    }

    public function store(Request $request)
    {
        $allowedServiceIds = RoleHelper::allowedServiceIds(Auth::user()?->id_rol);

        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'description'   => 'nullable|string|max:500',
            'price'         => 'required|numeric|min:0',
            'validity_days' => 'required|integer|min:1',
            'active'        => 'nullable|boolean',
            'credits'       => 'required|array|min:1',
            'credits.*'     => 'nullable|numeric|min:0',
        ]);

        $this->validateCredits($data, $allowedServiceIds);

        DB::transaction(function () use ($data, $request) {
            $package = CreditPackage::create([
                'name'          => $data['name'],
                'description'   => $data['description'] ?? null,
                'price'         => $data['price'],
                'validity_days' => $data['validity_days'],
                'active'        => $request->boolean('active', true),
            ]);

            foreach ($data['credits'] as $providerServiceId => $credits) {
                if ($credits !== null && $credits > 0) {
                    CreditPackageItem::create([
                        'credit_package_id'   => $package->id,
                        'provider_service_id' => $providerServiceId,
                        'credits'             => $credits,
                    ]);
                }
            }
        });

        return redirect()->route('admin.packages.index')->with('status', 'Paquete creado correctamente.');
    }

    public function edit(int $id)
    {
        $package = CreditPackage::with('items')->findOrFail($id);
        $services = $this->allowedServices();
        $itemsByService = $package->items->keyBy('provider_service_id');

        return view('admin.packages.edit', compact('package', 'services', 'itemsByService'));
    }

    public function update(Request $request, int $id)
    {
        $allowedServiceIds = RoleHelper::allowedServiceIds(Auth::user()?->id_rol);

        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'description'   => 'nullable|string|max:500',
            'price'         => 'required|numeric|min:0',
            'validity_days' => 'required|integer|min:1',
            'active'        => 'nullable|boolean',
            'credits'       => 'required|array|min:1',
            'credits.*'     => 'nullable|numeric|min:0',
        ]);

        $this->validateCredits($data, $allowedServiceIds);

        DB::transaction(function () use ($data, $request, $id) {
            $package = CreditPackage::findOrFail($id);
            $package->update([
                'name'          => $data['name'],
                'description'   => $data['description'] ?? null,
                'price'         => $data['price'],
                'validity_days' => $data['validity_days'],
                'active'        => $request->boolean('active', true),
            ]);

            $package->items()->delete();
            foreach ($data['credits'] as $providerServiceId => $credits) {
                if ($credits !== null && $credits > 0) {
                    CreditPackageItem::create([
                        'credit_package_id'   => $package->id,
                        'provider_service_id' => $providerServiceId,
                        'credits'             => $credits,
                    ]);
                }
            }
        });

        return redirect()->route('admin.packages.index')->with('status', 'Paquete actualizado correctamente.');
    }

    private function validateCredits(array $data, array $allowedServiceIds): void
    {
        foreach (array_keys($data['credits'] ?? []) as $providerServiceId) {
            if (! in_array((int) $providerServiceId, $allowedServiceIds, true)) {
                abort(403, 'Servicio no permitido para tu rol.');
            }
        }
    }

    public function destroy(int $id)
    {
        CreditPackage::findOrFail($id)->delete();

        return redirect()->route('admin.packages.index')->with('status', 'Paquete eliminado.');
    }

    public function assignForm()
    {
        $packages = CreditPackage::where('active', true)
            ->with('items')
            ->orderBy('name')
            ->get();
        $users = User::orderBy('name')->get();

        $userAllowedPackageIds = $users->mapWithKeys(function ($user) use ($packages) {
            $allowedServiceIds = RoleHelper::allowedServiceIds($user->id_rol);

            $allowedPackageIds = $packages
                ->filter(fn ($package) => $package->items->every(
                    fn ($item) => in_array((int) $item->provider_service_id, $allowedServiceIds, true)
                ))
                ->pluck('id')
                ->all();

            return [$user->id => $allowedPackageIds];
        })->all();

        return view('admin.packages.assign', compact('packages', 'users', 'userAllowedPackageIds'));
    }

    public function assign(Request $request)
    {
        $data = $request->validate([
            'user_id'    => 'required|exists:users,id',
            'package_id' => 'required|exists:credit_packages,id',
            'notes'      => 'nullable|string|max:255',
        ]);

        $user    = User::findOrFail($data['user_id']);
        $package = CreditPackage::with('items.service')->findOrFail($data['package_id']);

        $allowedServiceIds = RoleHelper::allowedServiceIds($user->id_rol);
        foreach ($package->items as $item) {
            if (! in_array((int) $item->provider_service_id, $allowedServiceIds, true)) {
                abort(403, 'El paquete incluye servicios no permitidos para el rol del usuario seleccionado.');
            }
        }

        $adminId   = Auth::id();
        $validityDays = (int) $package->validity_days;
        $expiresAt = now()->addDays($validityDays);

        DB::transaction(function () use ($data, $package, $adminId, $expiresAt) {
            UserPackage::create([
                'user_id'           => $data['user_id'],
                'credit_package_id' => $package->id,
                'assigned_at'       => now(),
                'expires_at'        => $expiresAt,
                'assigned_by'       => $adminId,
                'notes'             => $data['notes'] ?? null,
            ]);

            foreach ($package->items as $item) {
                $correlationId = 'pkg-' . $package->id . '-u' . $data['user_id'] . '-s' . $item->provider_service_id . '-' . time();
                $command = new AddCreditsCommand(
                    userId: $data['user_id'],
                    providerServiceId: $item->provider_service_id,
                    amount: (float) $item->credits,
                    reason: 'Asignación de paquete: ' . $package->name,
                    correlationId: $correlationId,
                    adminId: $adminId,
                    validityEnd: DateTimeImmutable::createFromMutable($expiresAt->toDateTime()),
                );
                $this->addCreditsHandler->handle($command);
            }
        });

        return redirect()->route('admin.packages.index')
            ->with('status', 'Paquete "' . $package->name . '" asignado correctamente. Créditos acreditados al usuario.');
    }

    public function active(Request $request)
    {
        $query = UserPackage::with(['user', 'package', 'assignedBy'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('package_id')) {
            $query->where('credit_package_id', $request->input('package_id'));
        }

        if ($request->filled('status')) {
            $now = now();
            match ($request->input('status')) {
                'active' => $query->where(function ($q) use ($now) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
                }),
                'expired' => $query->whereNotNull('expires_at')->where('expires_at', '<=', $now),
                default => null,
            };
        }

        if ($request->filled('date_from')) {
            $query->whereDate('assigned_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('assigned_at', '<=', $request->input('date_to'));
        }

        $kpis = $this->buildPackageKpis((clone $query)->get());
        $userPackages = $query->paginate(20)->withQueryString();

        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $packages = CreditPackage::orderBy('name')->get(['id', 'name']);
        $statuses = ['active' => 'Activo', 'expired' => 'Expirado'];

        return view('admin.packages.active', compact('userPackages', 'users', 'packages', 'statuses', 'kpis'));
    }

    private function buildPackageKpis($userPackages): array
    {
        return [
            'total' => $userPackages->count(),
            'active' => $userPackages->filter(fn ($up) => ! $up->isExpired())->count(),
            'expired' => $userPackages->filter(fn ($up) => $up->isExpired())->count(),
            'users' => $userPackages->pluck('user_id')->unique()->count(),
        ];
    }
}
