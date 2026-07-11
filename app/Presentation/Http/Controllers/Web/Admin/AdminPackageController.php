<?php

namespace App\Presentation\Http\Controllers\Web\Admin;

use App\Application\Credits\CommandHandlers\AddCreditsCommandHandler;
use App\Application\Credits\Commands\AddCreditsCommand;
use App\Infrastructure\Persistence\Models\CreditPackage;
use App\Infrastructure\Persistence\Models\CreditPackageItem;
use App\Infrastructure\Persistence\Models\Provider;
use App\Infrastructure\Persistence\Models\UserPackage;
use App\Models\User;
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
        $packages = CreditPackage::with('items.provider')->orderBy('name')->get();

        return view('admin.packages.index', compact('packages'));
    }

    public function create()
    {
        $providers = Provider::where('enabled', true)->orderBy('name')->get();

        return view('admin.packages.create', compact('providers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'description'   => 'nullable|string|max:500',
            'price'         => 'required|numeric|min:0',
            'validity_days' => 'required|integer|min:1',
            'active'        => 'nullable|boolean',
            'credits'       => 'required|array|min:1',
            'credits.*'     => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($data, $request) {
            $package = CreditPackage::create([
                'name'          => $data['name'],
                'description'   => $data['description'] ?? null,
                'price'         => $data['price'],
                'validity_days' => $data['validity_days'],
                'active'        => $request->boolean('active', true),
            ]);

            foreach ($data['credits'] as $providerId => $credits) {
                if ($credits !== null && $credits > 0) {
                    CreditPackageItem::create([
                        'credit_package_id' => $package->id,
                        'provider_id'       => $providerId,
                        'credits'           => $credits,
                    ]);
                }
            }
        });

        return redirect()->route('admin.packages.index')->with('status', 'Paquete creado correctamente.');
    }

    public function edit(int $id)
    {
        $package   = CreditPackage::with('items')->findOrFail($id);
        $providers = Provider::where('enabled', true)->orderBy('name')->get();

        $itemsByProvider = $package->items->keyBy('provider_id');

        return view('admin.packages.edit', compact('package', 'providers', 'itemsByProvider'));
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'description'   => 'nullable|string|max:500',
            'price'         => 'required|numeric|min:0',
            'validity_days' => 'required|integer|min:1',
            'active'        => 'nullable|boolean',
            'credits'       => 'required|array|min:1',
            'credits.*'     => 'nullable|numeric|min:0',
        ]);

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
            foreach ($data['credits'] as $providerId => $credits) {
                if ($credits !== null && $credits > 0) {
                    CreditPackageItem::create([
                        'credit_package_id' => $package->id,
                        'provider_id'       => $providerId,
                        'credits'           => $credits,
                    ]);
                }
            }
        });

        return redirect()->route('admin.packages.index')->with('status', 'Paquete actualizado correctamente.');
    }

    public function destroy(int $id)
    {
        CreditPackage::findOrFail($id)->delete();

        return redirect()->route('admin.packages.index')->with('status', 'Paquete eliminado.');
    }

    public function assignForm()
    {
        $packages = CreditPackage::where('active', true)->orderBy('name')->get();
        $users    = User::orderBy('name')->get();

        return view('admin.packages.assign', compact('packages', 'users'));
    }

    public function assign(Request $request)
    {
        $data = $request->validate([
            'user_id'    => 'required|exists:users,id',
            'package_id' => 'required|exists:credit_packages,id',
            'notes'      => 'nullable|string|max:255',
        ]);

        $package   = CreditPackage::with('items.provider')->findOrFail($data['package_id']);
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
                $correlationId = 'pkg-' . $package->id . '-u' . $data['user_id'] . '-p' . $item->provider_id . '-' . time();
                $command = new AddCreditsCommand(
                    userId: $data['user_id'],
                    providerId: $item->provider_id,
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
}
