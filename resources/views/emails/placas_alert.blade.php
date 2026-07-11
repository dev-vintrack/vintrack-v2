@php
    use App\Presentation\Support\PlacasReportPresenter as P;
    $titles = P::sectionTitles();
    $userEmail = $userInfo['email'] ?? '';
    $nombre = $userInfo['nombre'] ?? '';
    $telefono = $userInfo['telefono'] ?? '';
    $rol = strtolower((string) ($userInfo['rol'] ?? ''));
    $esOficial = $rol === 'oficial' ? 'Sí' : 'No';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family:Arial,Helvetica,sans-serif; color:#333;">
    <h2 style="color:#dc3545;">Alerta de posible reporte de robo o recuperado</h2>
    <p><strong>Criterio:</strong> {{ strtoupper($criterio) }} | <strong>Valor:</strong> {{ strtoupper($valor) }}</p>

    <p>
        <strong>Usuario:</strong> {{ $userEmail }}<br>
        <strong>Nombre:</strong> {{ $nombre !== '' ? $nombre : '—' }}<br>
        <strong>Teléfono:</strong> {{ $telefono !== '' ? $telefono : '—' }}<br>
        <strong>Oficial:</strong> {{ $esOficial }}
    </p>

    <div style="margin:10px 0; padding:10px; background:#fff3cd; border:1px solid #ffeeba; border-radius:6px;">
        <p style="margin:0 0 6px 0;"><strong>Pasos a seguir (Acciones)</strong></p>
        <p style="margin:0;">Para proteger tu libertad y patrimonio, actúa con rapidez:</p>
        <ul style="margin:6px 0 0 18px;">
            <li>No intentes esconder el vehículo: Si te lo descubre la policía, entrégalo pacíficamente.</li>
            <li>Reúne todas tus pruebas: Guarda el contrato de compraventa, recibos de pago, mensajes y datos de identificación del vendedor.</li>
            <li>Acude a la Fiscalía: Presenta una denuncia formal contra quien te vendió el auto por el delito de fraude.</li>
        </ul>
        <p style="margin:10px 0 0 0;">
            <strong>¿Cómo puedo denunciar el robo de un vehículo por internet?</strong><br>
            La fiscalía desarrolló el Sistema de Denuncias Virtual:
            <a href="https://denuncia.fiscalia-nl.gob.mx:8443/">https://denuncia.fiscalia-nl.gob.mx:8443/</a>,
            así como una aplicación para el celular. La plataforma permite denunciar cualquier tipo de delito.
        </p>
        <p style="margin:10px 0 0 0;">
            <strong>Línea de emergencia:</strong><br>
            Actúa de inmediato comunícate al 9-1-1 para emergencias. Proporciona a las autoridades el número de placa,
            la marca y la serie o VIN para facilitar su rápida identificación.
        </p>
    </div>

    @foreach($titles as $key => $title)
        @php
            $data = $sections[$key] ?? null;
            $rows = P::flatten($data);
        @endphp
        @if(!empty($rows))
            <h3 style="margin:14px 0 6px 0; font-size:16px;">{{ $title }}</h3>
            <table style="width:100%; border-collapse:collapse; margin:10px 0;">
                <thead>
                    <tr><th colspan="2" style="text-align:left; background:#0d2238; color:#fff; padding:8px;">{{ $title }}</th></tr>
                </thead>
                <tbody>
                    @foreach($rows as [$rawKey, $value])
                        @php
                            $label = P::prettyKey($rawKey);
                            $isAlert = P::isAlertRow($title, $label, (string) $value);
                        @endphp
                        <tr>
                            <th style="width:40%; background:#f2f2f2; padding:8px; text-align:left;">{{ $label }}</th>
                            <td style="padding:8px; {{ $isAlert ? 'color:#dc3545;font-weight:700;' : '' }}">{{ $value }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach

    <p style="color:#777;">© {{ date('Y') }} VINTRACK</p>
</body>
</html>
