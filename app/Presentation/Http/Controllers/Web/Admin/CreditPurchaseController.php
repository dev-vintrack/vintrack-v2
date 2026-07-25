<?php

namespace App\Presentation\Http\Controllers\Web\Admin;

use App\Application\Credits\CommandHandlers\AddCreditsCommandHandler;
use App\Application\Credits\Commands\AddCreditsCommand;
use App\Infrastructure\Persistence\Models\GlobalConfiguration;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Infrastructure\Persistence\Models\UserPackage;
use App\Infrastructure\Persistence\Models\UserProviderWallet;
use App\Models\User;
use App\Presentation\Support\RoleHelper;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
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

        $config = GlobalConfiguration::settings();

        $validityOptions = [];
        for ($days = (int) $config->min_validity_days; $days <= (int) $config->max_validity_days; $days += (int) $config->step_validity_input) {
            $validityOptions[] = $days;
        }

        return view('admin.credits.purchase', compact(
            'users',
            'services',
            'userAllowedServices',
            'config',
            'validityOptions'
        ));
    }

    public function walletInfo(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'provider_service_id' => 'required|exists:provider_services,id',
        ]);

        $wallet = UserProviderWallet::where('user_id', $data['user_id'])
            ->where('provider_service_id', $data['provider_service_id'])
            ->first();

        $hasActivePackage = $this->hasActivePackage((int) $data['user_id']);

        return response()->json([
            'balance' => $wallet ? (float) $wallet->balance : 0,
            'validity_start' => $wallet?->validity_start?->format('d/m/Y H:i'),
            'validity_end' => $wallet?->validity_end?->format('d/m/Y H:i'),
            'has_active_package' => $hasActivePackage,
        ]);
    }

    public function store(Request $request)
    {
        $config = GlobalConfiguration::settings();

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'provider_service_id' => 'required|exists:provider_services,id',
            'amount' => 'required|numeric|min:' . $config->min_purchase_user . '|max:' . $config->max_purchase_user,
            'validity_days' => 'required|integer|min:' . $config->min_validity_days . '|max:' . $config->max_validity_days,
            'reason' => 'required|string|max:255',
        ]);

        $user = User::findOrFail($data['user_id']);
        $providerService = ProviderService::findOrFail($data['provider_service_id']);

        if (! $providerService->enabled || ! RoleHelper::isServiceAllowed($user->id_rol, $providerService->id)) {
            abort(403, 'Servicio no permitido para el rol del usuario seleccionado.');
        }

        if ($this->hasActivePackage($user->id)) {
            return redirect()->back()
                ->with('status', 'No puedes agregar créditos directos a un usuario con un paquete activo vigente.')
                ->withInput();
        }

        $correlationId = 'purchase-' . $data['provider_service_id'] . '-' . $data['user_id'] . '-' . time();

        $validityDays = (int) $data['validity_days'];
        $assignedAt = now();
        $validityStart = DateTimeImmutable::createFromMutable($assignedAt->toDateTime());
        $validityEnd = DateTimeImmutable::createFromMutable($assignedAt->copy()->addDays($validityDays)->toDateTime());

        $command = new AddCreditsCommand(
            $data['user_id'],
            $data['provider_service_id'],
            (float) $data['amount'],
            $data['reason'],
            $correlationId,
            Auth::id(),
            $validityStart,
            $validityEnd
        );

        $this->addCreditsHandler->handle($command);

        return redirect()->route('admin.credits.purchase')->with('status', 'Créditos agregados correctamente.');
    }

    private function hasActivePackage(int $userId): bool
    {
        UserPackage::syncExpiredStatuses();

        return UserPackage::where('user_id', $userId)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }
}
