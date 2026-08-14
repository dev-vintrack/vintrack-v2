<?php

namespace App\Presentation\Http\Controllers\Web\Admin;

use App\Application\NotificationCases\Services\NotificationCaseAuthorizationService;
use App\Application\NotificationCases\Services\NotificationCaseDocumentService;
use App\Application\NotificationCases\Services\NotificationCaseLifecycleService;
use App\Domain\NotificationCases\Enums\NotificationCaseStatus;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\NotificationCase;
use App\Models\User;
use App\Presentation\Http\Requests\NotificationCases\AdminTransitionNotificationCaseRequest;
use App\Presentation\Http\Requests\NotificationCases\AdminUpdateNotificationCaseRequest;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AdminNotificationCaseController extends Controller
{
    public function __construct(private readonly NotificationCaseAuthorizationService $authorization, private readonly NotificationCaseLifecycleService $lifecycle, private readonly NotificationCaseDocumentService $documents) {}

    public function index(Request $request): View
    {
        $filters = $request->validate(['folio' => ['nullable', 'string', 'max:32'], 'vin' => ['nullable', 'string', 'max:32'], 'user_id' => ['nullable', 'integer', 'exists:users,id'], 'status' => ['nullable', 'string', 'in:'.implode(',', array_column(NotificationCaseStatus::cases(), 'value'))], 'date_from' => ['nullable', 'date'], 'date_to' => ['nullable', 'date', 'after_or_equal:date_from']]);
        $query = NotificationCase::query()->with('owner')->withCount(['documents as active_documents_count' => fn ($q) => $q->whereNull('removed_at')]);
        $query->when($filters['folio'] ?? null, fn ($q, $value) => $q->where('case_number', 'like', '%'.addcslashes($value, '%_').'%'))
            ->when($filters['vin'] ?? null, fn ($q, $value) => $q->where('vin_key', strtoupper(trim($value))))
            ->when($filters['user_id'] ?? null, fn ($q, $value) => $q->where('user_id', $value))
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->where('status', $value))
            ->when($filters['date_from'] ?? null, fn ($q, $value) => $q->whereDate('opened_at', '>=', $value))
            ->when($filters['date_to'] ?? null, fn ($q, $value) => $q->whereDate('opened_at', '<=', $value));
        $cases = $query->orderByRaw("CASE status WHEN 'SUBMITTED' THEN 0 WHEN 'UNDER_REVIEW' THEN 1 ELSE 2 END")->orderByDesc('updated_at')->orderByDesc('id')->paginate(20)->withQueryString();
        $owners = User::whereHas('consultations')->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.notification-cases.index', compact('cases', 'owners'));
    }

    public function show(Request $request, NotificationCase $case): View
    {
        abort_unless($this->authorization->canView($request->user(), $case), 403);
        $case->load(['owner', 'consultation', 'events' => fn ($q) => $q->with('actor')]);
        $documents = $this->documents->list($case, $request->user());

        return view('admin.notification-cases.show', ['case' => $case, 'documents' => $documents, 'editable' => $this->authorization->canEditAsAdministrator($request->user(), $case)]);
    }

    public function update(AdminUpdateNotificationCaseRequest $request, NotificationCase $case): RedirectResponse
    {
        return $this->run($case, fn () => $this->lifecycle->saveAdministrativeCorrections($request->user(), $case->id, $request->correctionFields(), $request->integer('lock_version'), $request->string('request_key')->toString()), 'Cambios administrativos guardados.');
    }

    public function startReview(AdminTransitionNotificationCaseRequest $request, NotificationCase $case): RedirectResponse
    {
        return $this->transition($request, $case, NotificationCaseStatus::UNDER_REVIEW, 'RevisiÃ³n iniciada.');
    }

    public function validateCase(AdminTransitionNotificationCaseRequest $request, NotificationCase $case): RedirectResponse
    {
        return $this->transition($request, $case, NotificationCaseStatus::VALIDATED, 'Expediente validado.');
    }

    public function reject(AdminTransitionNotificationCaseRequest $request, NotificationCase $case): RedirectResponse
    {
        return $this->transition($request, $case, NotificationCaseStatus::REJECTED, 'Expediente rechazado.', $request->string('reason')->toString());
    }

    private function transition(AdminTransitionNotificationCaseRequest $request, NotificationCase $case, NotificationCaseStatus $to, string $message, ?string $reason = null): RedirectResponse
    {
        return $this->run($case, fn () => $this->lifecycle->transition($request->user(), $case->id, $to, $request->integer('lock_version'), $request->string('request_key')->toString(), $reason), $message);
    }

    private function run(NotificationCase $case, callable $operation, string $message): RedirectResponse
    {
        try {
            $operation();
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['case' => $exception->getMessage()]);
        }

        return redirect()->route('admin.notification-cases.show', $case)->with('success', $message);
    }
}
