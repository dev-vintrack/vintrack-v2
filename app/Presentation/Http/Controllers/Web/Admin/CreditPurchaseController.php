<?php

namespace App\Presentation\Http\Controllers\Web\Admin;

use App\Application\Credits\CommandHandlers\AddCreditsCommandHandler;
use App\Application\Credits\Commands\AddCreditsCommand;
use App\Infrastructure\Persistence\Models\Provider;
use App\Models\User;
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
        $providers = Provider::where('enabled', true)->get();

        return view('admin.credits.purchase', compact('users', 'providers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'provider_id' => 'required|exists:providers,id',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
        ]);

        $correlationId = 'purchase-' . $data['provider_id'] . '-' . $data['user_id'] . '-' . time();

        $command = new AddCreditsCommand(
            $data['user_id'],
            $data['provider_id'],
            (float) $data['amount'],
            $data['reason'],
            $correlationId,
            Auth::id()
        );

        $this->addCreditsHandler->handle($command);

        return redirect()->route('admin.credits.purchase')->with('status', 'Créditos agregados correctamente.');
    }
}
