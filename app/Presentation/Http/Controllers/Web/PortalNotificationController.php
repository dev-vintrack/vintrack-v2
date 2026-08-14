<?php

namespace App\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\PortalNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PortalNotificationController extends Controller
{
    public function index(Request $request): View
    {
        abort_if($request->has('recipient_user_id'), 400);
        $notifications = PortalNotification::query()
            ->where('recipient_user_id', $request->user()->id)
            ->orderByDesc('created_at')->orderByDesc('id')->paginate(20);

        return view('customer.notifications.index', compact('notifications'));
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['count' => PortalNotification::query()
            ->where('recipient_user_id', $request->user()->id)->whereNull('read_at')->count()]);
    }

    public function markRead(Request $request, PortalNotification $notification): RedirectResponse
    {
        abort_unless($notification->recipient_user_id === $request->user()->id, 404);
        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        return redirect()->to($this->authorizedDestination($notification));
    }

    private function authorizedDestination(PortalNotification $notification): string
    {
        if ($notification->case_id !== null) {
            return route('customer.notification-cases.show', ['case' => $notification->case_id]);
        }

        return route('customer.notifications.index');
    }
}
