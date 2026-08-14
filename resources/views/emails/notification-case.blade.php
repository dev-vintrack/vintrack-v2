<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><title>{{ $title }}</title></head>
<body>
    <h1>{{ $title }}</h1>
    <p>{{ $messageText }}</p>
    @if ($caseNumber)<p>Expediente: <strong>{{ $caseNumber }}</strong></p>@endif
    @if ($actionUrl)<p><a href="{{ $actionUrl }}">Consultar expediente</a></p>@endif
    <p>VINTrack administra evidencia documental y no sustituye a las autoridades competentes.</p>
</body>
</html>
