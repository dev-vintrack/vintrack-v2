<?php

namespace App\Presentation\Http\Controllers\Web;

use App\Application\NotificationCases\Services\NotificationCaseAuthorizationService;
use App\Application\NotificationCases\Services\NotificationCaseDocumentService;
use App\Application\NotificationCases\Services\NotificationCaseLifecycleService;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\NotificationCase;
use App\Presentation\Http\Requests\NotificationCases\SaveNotificationCaseDraftRequest;
use App\Presentation\Http\Requests\NotificationCases\SubmitNotificationCaseRequest;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CustomerNotificationCaseController extends Controller
{
    public function __construct(
        private readonly NotificationCaseAuthorizationService $authorization,
        private readonly NotificationCaseLifecycleService $lifecycle,
        private readonly NotificationCaseDocumentService $documents,
    ) {}

    public function index(Request $request): View
    {
        abort_if($request->has('user_id'), 400, 'El filtro de usuario no está permitido.');
        $cases = NotificationCase::query()->with('consultation')->where('user_id', $request->user()->id)
            ->withCount(['documents as active_documents_count' => fn ($query) => $query->whereNull('removed_at')])
            ->orderByDesc('updated_at')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('customer.notification-cases.index', compact('cases'));
    }

    public function show(Request $request, NotificationCase $case): View
    {
        $this->authorizeOwn($request, $case);
        $case->load('consultation');
        $documents = $this->documents->list($case, $request->user());
        $latestRejection = $case->events()->where('event_type', 'CASE_REJECTED')->latest('occurred_at')->latest('id')->first();

        return view('customer.notification-cases.show', [
            'case' => $case,
            'documents' => $documents,
            'editable' => $this->authorization->canEditOwn($request->user(), $case),
            'canSubmit' => $this->authorization->canSubmit($request->user(), $case),
            'latestRejection' => $latestRejection,
        ]);
    }

    public function update(SaveNotificationCaseDraftRequest $request, NotificationCase $case): RedirectResponse
    {
        $this->authorizeOwn($request, $case);
        try {
            $this->lifecycle->saveDraft($request->user(), $case->id, $request->draftFields(), $request->integer('lock_version'), $request->string('request_key')->toString());
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['case' => $exception->getMessage()]);
        }

        return redirect()->route('customer.notification-cases.show', $case)->with('success', 'Borrador guardado correctamente.');
    }

    public function submit(SubmitNotificationCaseRequest $request, NotificationCase $case): RedirectResponse
    {
        $this->authorizeOwn($request, $case);
        try {
            $this->lifecycle->submit($request->user(), $case->id, $request->integer('lock_version'), $request->string('request_key')->toString());
        } catch (DomainException $exception) {
            return back()->withErrors(['case' => $exception->getMessage()]);
        }

        return redirect()->route('customer.notification-cases.show', $case)->with('success', 'Expediente enviado correctamente.');
    }

    private function authorizeOwn(Request $request, NotificationCase $case): void
    {
        abort_unless($case->user_id === $request->user()->id && $this->authorization->canView($request->user(), $case), 404);
    }
}
