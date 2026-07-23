<?php

namespace App\Presentation\Http\Controllers\Web\Admin;

use App\Application\Credits\CommandHandlers\AddCreditsCommandHandler;
use App\Application\Credits\Commands\AddCreditsCommand;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Models\User;
use App\Presentation\Support\RoleHelper;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CreditPurchaseController
{
    public function __construct(
        private readonly AddCreditsCommandHandler $addCreditsHandler
    ) {
    }

    public function create()
    {
        $users = User::orderBy('name')->get();
        $services = ProviderService::with('provider')
            ->where('enabled', true)
            ->orderBy('provider_id')
            ->orderBy('name')
            ->get();

        $userAllowedServices = $users->mapWithKeys(
            fn ($user) => [$user->id => RoleHelper::allowedServiceIds($user->id_rol)]
        )->all();

        return view('admin.credits.purchase', compact('users', 'services', 'userAllowedServices'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'provider_service_id' => 'required|exists:provider_services,id',
            'amount' => 'required|numeric|min:0.01',
            'validity_days' => 'nullable|integer|min:1',
            'reason' => 'required|string|max:255',
        ]);

        $user = User::findOrFail($data['user_id']);
        $providerService = ProviderService::findOrFail($data['provider_service_id']);

        if (! $providerService->enabled || ! RoleHelper::isServiceAllowed($user->id_rol, $providerService->id)) {
            abort(403, 'Servicio no permitido para el rol del usuario seleccionado.');
        }

        $correlationId = 'purchase-' . $data['provider_service_id'] . '-' . $data['user_id'] . '-' . time();

        $validityEnd = null;
        if (!empty($data['validity_days'])) {
            $validityEnd = DateTimeImmutable::createFromMutable(now()->addDays((int) $data['validity_days'])->toDateTime());
        }

        $command = new AddCreditsCommand(
            $data['user_id'],
            $data['provider_service_id'],
            (float) $data['amount'],
            $data['reason'],
            $correlationId,
            Auth::id(),
            $validityEnd
        );

        $this->addCreditsHandler->handle($command);

        return redirect()->route('admin.credits.purchase')->with('status', 'Créditos agregados correctamente.');
    }
}
