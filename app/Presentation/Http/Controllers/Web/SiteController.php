<?php

namespace App\Presentation\Http\Controllers\Web;

use App\Application\Site\Services\HomePageService;
use App\Application\Site\Services\VinDecoder;
use App\Infrastructure\Persistence\Models\CreditPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SiteController
{
    public function __construct(
        private readonly HomePageService $homePageService,
        private readonly VinDecoder $vinDecoder
    ) {
    }

    public function home(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('site.home', $this->homePageService->stats());
    }

    public function about(): View
    {
        return view('site.about');
    }

    public function services(): View
    {
        $packages = CreditPackage::with(['items.service'])
            ->where('active', true)
            ->orderBy('price')
            ->get();

        return view('site.services', compact('packages'));
    }

    public function training(): View
    {
        return view('site.training');
    }

    public function contact(): View
    {
        return view('site.contact');
    }

    public function sendContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'correo' => 'required|email|max:255',
            'telefono' => 'nullable|string|max:20',
            'mensaje' => 'required|string|max:2000',
        ]);

        $body = "Mensaje de contacto - VINTRACK\n\n";
        $body .= "Nombre: {$data['nombre']}\n";
        $body .= "Correo: {$data['correo']}\n";
        $body .= "Teléfono: " . ($data['telefono'] ?: 'No proporcionado') . "\n\n";
        $body .= "Mensaje:\n{$data['mensaje']}";

        Mail::raw($body, function ($mail) use ($data) {
            $mail->to(config('vintrack.contact_email'))
                ->from('noreply@vintrack.com.mx', 'VINTRACK')
                ->replyTo($data['correo'], $data['nombre'])
                ->subject('Mensaje de contacto - VINTRACK');
        });

        return back()->with('status', 'Mensaje enviado correctamente');
    }

    public function sales(): View
    {
        return view('site.sales');
    }

    public function sendSalesInquiry(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string|max:2000',
        ]);

        $body = "Solicitud de compra única - VINTRACK\n\n";
        $body .= "Nombre: {$data['name']}\n";
        $body .= "Correo: {$data['email']}\n\n";
        $body .= "Mensaje:\n{$data['message']}";

        Mail::raw($body, function ($mail) use ($data) {
            $mail->to(config('vintrack.ventas_email'))
                ->from('noreply@vintrack.com.mx', 'VINTRACK')
                ->replyTo($data['email'], $data['name'])
                ->subject('Solicitud de compra única - VINTRACK');
        });

        return back()->with('status', 'Mensaje enviado. Un asesor comercial te indicará la forma y el proceso de pago, para que recibas el PDF de tu reporte completo.');
    }

    public function decodeVin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vin' => 'required|string|min:11|max:17',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
        ]);

        $year = ! empty($data['year']) ? (int) $data['year'] : null;

        $result = $this->vinDecoder->decode($data['vin'], $year);

        if ($result === null) {
            return response()->json(['error' => 'No fue posible decodificar el VIN. Verifica el número e intenta de nuevo.'], 422);
        }

        if (! empty($result['error_code']) && str_contains((string) $result['error_code'], '7')) {
            return response()->json(['error' => 'El VIN ingresado no es válido o no se encontró información.'], 422);
        }

        return response()->json(['data' => $result]);
    }
}
