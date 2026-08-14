<?php

namespace App\Presentation\Http\Controllers\Web\Admin;

use App\Application\ConsultationHistories\ConsultationHistoryQuery;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminConsultationController
{
    public function index(Request $request)
    {
        $this->authorizeHistory($request);
        $users = User::whereHas('role.roleType', fn ($query) => $query->where('is_customer', true))
            ->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.consultations.index', compact('users'));
    }

    public function data(Request $request, ConsultationHistoryQuery $history): JsonResponse
    {
        $this->authorizeHistory($request);
        $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date'], 'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'vin' => ['nullable', 'string', 'max:32'], 'plate' => ['nullable', 'string', 'max:32'],
            'theft_status' => ['nullable', 'in:POSITIVO,NEGATIVO'],
            'case_status' => ['nullable', 'in:NO_CASE,PENDING,SUBMITTED,UNDER_REVIEW,REJECTED,VALIDATED,CLOSED_NO_FOLLOW_UP'],
        ]);

        return response()->json($history->data($request, null));
    }

    private function authorizeHistory(Request $request): void
    {
        abort_unless(in_array($request->user()?->rol, ['admin', 'analista'], true), 403);
    }
}
