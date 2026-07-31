<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family:Arial,Helvetica,sans-serif; color:#333;">
    <h2 style="color:#0d2238;">Tus créditos están por agotarse</h2>
    <p>Hola {{ $wallet->user?->name ?? 'Cliente' }},</p>
    <p>El saldo del servicio <strong>{{ $service->name }}</strong> es de <strong>{{ number_format($balance, 2) }}</strong> créditos y alcanzó el nivel de alerta de {{ number_format($threshold, 2) }}.</p>
    <p><strong>Proveedor:</strong> {{ $service->provider?->name ?? '—' }}</p>
    @if($wallet->validity_end)
        <p><strong>Vigencia:</strong> {{ $wallet->validity_end->format('d/m/Y H:i') }}</p>
    @endif
    <p>Te sugerimos adquirir más créditos para continuar realizando consultas.</p>
    <p><a href="{{ route('customer.credits') }}" style="display:inline-block;padding:10px 18px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:5px;">Consultar mis créditos</a></p>
    <p style="color:#777;">© {{ date('Y') }} VINTRACK</p>
</body>
</html>
