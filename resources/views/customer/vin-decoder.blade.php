@extends('layouts.app')

@section('title', 'VIN Decoder - VINTRACK')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
    <div>
        <h2 class="mb-1">VIN Decoder</h2>
        <p class="text-muted mb-0">Decodifica el VIN de un vehículo y consulta sus características técnicas.</p>
    </div>
    <span class="badge text-bg-light border px-3 py-2"><i class="bi bi-shield-check text-success me-1"></i> Información privada</span>
</div>

<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h2 class="fw-bold h4">Decodificador Vehicular</h2>
                    <p class="text-muted mb-0">Ingresa el VIN y obtén información técnica del vehículo en segundos.</p>
                </div>

                <form id="vinDecoderForm" action="{{ route('site.decode-vin') }}" method="POST">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-md-7">
                            <label for="vin" class="form-label">Número VIN (también acepta VIN menor a 17 caracteres)</label>
                            <input type="text" class="form-control form-control-lg" id="vin" name="vin" placeholder="Ej. 5UXWX7C5*BA" maxlength="17" required>
                        </div>
                        <div class="col-md-3">
                            <label for="year" class="form-label">Año (opcional)</label>
                            <input type="number" class="form-control" id="year" name="year" placeholder="2011" min="1900" max="{{ date('Y') + 1 }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100" id="decodeBtn">
                                <span class="spinner-border spinner-border-sm d-none" id="decodeSpinner" role="status" aria-hidden="true"></span>
                                Decodificar
                            </button>
                        </div>
                    </div>
                </form>

                <div id="decodeError" class="alert alert-danger mt-4 d-none" role="alert"></div>

                <div id="decodeResult" class="mt-4 d-none">
                    <h5 class="mb-3">Resultado</h5>
                    <div class="row g-4 align-items-start">
                        <div class="col-12 col-lg-8">
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0" id="vinResultTable">
                                    <tbody>
                                        <tr id="res-error-row">
                                            <th style="width: 160px;" id="res-error-label">Error</th>
                                            <td id="res-text" style="white-space: pre-line;"></td>
                                        </tr>
                                        <tr>
                                            <th>VIN</th>
                                            <td id="res-vin" class="vin-boxes-cell"></td>
                                        </tr>
                                        <tr id="res-proposed-row" class="d-none">
                                            <th>VIN Propuesto</th>
                                            <td>
                                                <div class="d-flex flex-column flex-sm-row align-items-start gap-2 flex-wrap">
                                                    <div id="res-vin-proposed" class="vin-boxes-cell"></div>
                                                    <div class="d-flex gap-2 align-items-start">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary text-nowrap" id="btnCopyProposed" data-bs-toggle="tooltip" data-bs-title="copiar VIN">
                                                            <i class="bi bi-clipboard me-1"></i> Copiar
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-info text-nowrap" id="btnShowCalc">
                                                            <i class="bi bi-calculator me-1"></i> Ver Cálculo
                                                        </button>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr><th>Marca</th><td id="res-make"></td></tr>
                                        <tr><th>Modelo</th><td id="res-model"></td></tr>
                                        <tr><th>Año</th><td id="res-year"></td></tr>
                                        <tr><th>Fabricante</th><td id="res-manufacturer"></td></tr>
                                        <tr><th>Versión</th><td id="res-trim"></td></tr>
                                        <tr><th>Carrocería</th><td id="res-body"></td></tr>
                                        <tr><th>Tracción</th><td id="res-drive"></td></tr>
                                        <tr><th>Combustible</th><td id="res-fuel"></td></tr>
                                        <tr><th>Cilindros</th><td id="res-cylinders"></td></tr>
                                        <tr><th>Modelo Motor</th><td id="res-engmod"></td></tr>
                                        <tr><th>HP</th><td id="res-hp"></td></tr>
                                        <tr><th>Desplazamiento</th><td id="res-displacement"></td></tr>
                                        <tr><th>Tipo</th><td id="res-type"></td></tr>
                                        <tr><th>País de origen</th><td id="res-country"></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="col-12 col-lg-4 d-none" id="calcCardWrapper">
                            <div class="card border-info h-100">
                                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 h6"><i class="bi bi-calculator me-2"></i>Desglose ISO 3779</h5>
                                    <button type="button" class="btn btn-sm btn-light text-info" id="btnHideCalc" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
                                </div>
                                <div class="card-body">
                                    <p class="mb-2 text-muted small" id="calcValidation"></p>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0" id="calcTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-center">Pos</th>
                                                    <th class="text-center">Carácter</th>
                                                    <th class="text-center">Valor</th>
                                                    <th class="text-center">Peso</th>
                                                    <th class="text-center">Producto</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-body border-top bg-light">
                                    <p class="mb-1"><strong>Suma Total:</strong> <span id="calcTotal">0</span></p>
                                    <p class="mb-1"><strong>Residuo MOD 11:</strong> <span id="calcMod">0</span> mod 11 = <span id="calcModResult" class="fw-bold">0</span></p>
                                    <p class="mb-1"><strong>Dígito verificador calculado:</strong> <span id="calcCheckDigit" class="fs-4 fw-bold text-success">0</span></p>
                                    <p class="mb-0 text-muted small">Conclusión: El dígito verificador calculado para la posición 9 es: <strong id="calcConclusion">0</strong>.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.vin-boxes-cell { display: flex; flex-wrap: nowrap; gap: .25rem; }
.vin-box {
    flex: 0 0 auto;
    min-width: 1.5rem;
    width: 1.5rem;
    height: 1.85rem;
    border: 1px solid #dee2e6;
    border-radius: .25rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: .85rem;
    background: #fff;
}
.vin-box.position-check { border-color: #0dcaf0; background-color: #e7f7ff; }
@media (max-width: 575.98px) {
    .vin-box { width: 1.35rem; height: 1.55rem; min-width: 1.35rem; font-size: .7rem; }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('vinDecoderForm');
        const btn = document.getElementById('decodeBtn');
        const yearInput = document.getElementById('year');
        const spinner = document.getElementById('decodeSpinner');
        const errorBox = document.getElementById('decodeError');
        const resultBox = document.getElementById('decodeResult');
        const calcCardWrapper = document.getElementById('calcCardWrapper');

        const fields = {
            make: 'res-make', model: 'res-model', year: 'res-year',
            trim: 'res-trim', body_class: 'res-body', drive_type: 'res-drive',
            fuel_type_primary: 'res-fuel', engine_cylinders: 'res-cylinders',
            engine_hp: 'res-hp', displacement_l: 'res-displacement',
            vehicle_type: 'res-type', plant_country: 'res-country',
            error_text: 'res-text', manufacturer: 'res-manufacturer', engine_model: 'res-engmod'
        };

        const vinValueMap = {
            A:1,B:2,C:3,D:4,E:5,F:6,G:7,H:8,
            J:1,K:2,L:3,M:4,N:5,P:7,R:9,
            S:2,T:3,U:4,V:5,W:6,X:7,Y:8,Z:9
        };
        const weights = [8,7,6,5,4,3,2,10,0,9,8,7,6,5,4,3,2];

        function setText(id, value) {
            const el = document.getElementById(id);
            if (el) el.textContent = value || '—';
        }

        function renderVinBoxes(containerId, vin, pos9Classes) {
            const container = document.getElementById(containerId);
            if (!container) return;
            container.innerHTML = '';
            if (!vin) return;
            const chars = String(vin).toUpperCase().split('');
            for (let i = 0; i < 17; i++) {
                const box = document.createElement('span');
                const extra = i === 8 ? (pos9Classes || 'position-check') : '';
                box.className = 'vin-box' + (extra ? ' ' + extra : '');
                box.textContent = chars[i] !== undefined ? chars[i] : '';
                box.title = `Posición ${i + 1}${i === 8 ? ' (dígito verificador)' : ''}`;
                container.appendChild(box);
            }
        }

        function calculateCheckDigit(vin) {
            const original = String(vin).toUpperCase();
            if (original.length !== 17) return { valid: false, error: 'El VIN debe tener 17 caracteres.' };
            if (/[IOQ]/.test(original)) return { valid: false, error: 'El VIN contiene caracteres prohibidos (I, O, Q).' };

            const rows = [];
            let total = 0;
            for (let i = 0; i < 17; i++) {
                const char = original[i];
                let value = null;
                if (/^[0-9]$/.test(char)) value = parseInt(char, 10);
                else if (vinValueMap[char] !== undefined) value = vinValueMap[char];
                else if (i === 8 && char === '*') value = 0;
                else return { valid: false, error: `Carácter inválido en posición ${i + 1}: ${char}` };

                const weight = weights[i];
                const product = value * weight;
                total += product;
                rows.push({ pos: i + 1, char, value, weight, product });
            }
            const mod = total % 11;
            const checkDigit = mod === 10 ? 'X' : String(mod);
            const proposedVin = original.substring(0, 8) + checkDigit + original.substring(9);
            return { valid: true, original, rows, total, mod, checkDigit, proposedVin };
        }

        async function copyToClipboard(text) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                try {
                    await navigator.clipboard.writeText(text);
                    return;
                } catch (e) {
                    // continuar con fallback
                }
            }
            return new Promise((resolve, reject) => {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.setAttribute('readonly', '');
                ta.style.position = 'fixed';
                ta.style.left = '-9999px';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                ta.setSelectionRange(0, 99999);
                try {
                    const ok = document.execCommand('copy');
                    document.body.removeChild(ta);
                    if (ok) resolve();
                    else reject(new Error('execCommand copy returned false'));
                } catch (err) {
                    document.body.removeChild(ta);
                    reject(err);
                }
            });
        }

        function renderCalculation(calc) {
            const validationEl = document.getElementById('calcValidation');
            if (validationEl) validationEl.textContent = 'Validación inicial: VIN de 17 caracteres, sin letras prohibidas (I, O, Q).';

            const tbody = document.querySelector('#calcTable tbody');
            if (!tbody) return;
            tbody.innerHTML = '';
            calc.rows.forEach(row => {
                const tr = document.createElement('tr');
                if (row.pos === 9) tr.classList.add('table-warning');
                tr.innerHTML = `
                    <td class="text-center fw-bold">${row.pos}</td>
                    <td class="text-center text-uppercase">${row.pos === 9 ? '<em>' + row.char + '</em>' : row.char}</td>
                    <td class="text-center">${row.value}</td>
                    <td class="text-center">${row.weight}</td>
                    <td class="text-center">${row.product}</td>
                `;
                tbody.appendChild(tr);
            });

            document.getElementById('calcTotal').textContent = calc.total;
            document.getElementById('calcMod').textContent = calc.total;
            document.getElementById('calcModResult').textContent = calc.mod;
            document.getElementById('calcCheckDigit').textContent = calc.checkDigit;
            document.getElementById('calcConclusion').textContent = calc.checkDigit;
        }

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            if (yearInput && !yearInput.value) {
                yearInput.placeholder = '';
            }

            errorBox.classList.add('d-none');
            resultBox.classList.add('d-none');
            calcCardWrapper.classList.add('d-none');
            spinner.classList.remove('d-none');
            btn.disabled = true;

            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const data = await response.json();

                if (!response.ok) {
                    errorBox.textContent = data.error || 'Ocurrió un error al decodificar el VIN.';
                    errorBox.classList.remove('d-none');
                    return;
                }

                for (const [key, id] of Object.entries(fields)) {
                    setText(id, data.data?.[key]);
                }

                const errorCodeRaw = data.data?.error_code;
                let errorCode = 0;
                const errorCodeMatches = String(errorCodeRaw ?? '').match(/\d+/g);
                if (errorCodeMatches && errorCodeMatches.length > 0) {
                    errorCode = parseInt(errorCodeMatches[0], 10);
                    if (isNaN(errorCode)) errorCode = 0;
                }

                const hasCheckDigitError = errorCodeMatches?.some(code => parseInt(code, 10) === 1) ?? false;

                const vinRaw = data.data?.vin || formData.get('vin') || '';
                const vin = String(vinRaw).toUpperCase().replace(/[^A-Z0-9*]/g, '');

                const pos9Classes = errorCode === 0
                    ? 'bg-success-subtle text-success border-success'
                    : (hasCheckDigitError ? 'bg-danger-subtle text-danger border-danger' : 'position-check');
                renderVinBoxes('res-vin', vin, pos9Classes);

                const errorRow = document.getElementById('res-error-row');
                const resErrorLabel = document.getElementById('res-error-label');
                if (resErrorLabel) resErrorLabel.textContent = errorCode === 0 ? 'Correcto' : 'Error';

                const resText = document.getElementById('res-text');
                if (resText) {
                    const rawError = data.data?.error_text || '—';
                    resText.textContent = rawError.includes(';') ? rawError.split(';').map(s => s.trim()).join('\n') : rawError;
                }

                const maxError = 8;
                const colorErrorCode = Math.min(Math.max(errorCode, 0), maxError);
                let hue;
                if (colorErrorCode === 0) hue = 120;
                else {
                    const t = (colorErrorCode - 1) / (maxError - 1);
                    hue = 60 - (t * 60);
                }
                const rowColor = `hsl(${hue}, 80%, 85%)`;
                if (errorRow) {
                    errorRow.style.backgroundColor = rowColor;
                    errorRow.querySelectorAll('th, td').forEach(cell => cell.style.backgroundColor = rowColor);
                }

                const proposedRow = document.getElementById('res-proposed-row');
                let calc = null;
                if (hasCheckDigitError && vin.length === 17) {
                    calc = calculateCheckDigit(vin);
                    if (calc.valid) {
                        renderVinBoxes('res-vin-proposed', calc.proposedVin, 'bg-success-subtle text-success border-success');
                        proposedRow.classList.remove('d-none');
                        document.getElementById('btnShowCalc').onclick = function () {
                            renderCalculation(calc);
                            calcCardWrapper.classList.remove('d-none');
                        };
                    } else {
                        proposedRow.classList.add('d-none');
                    }
                } else {
                    proposedRow.classList.add('d-none');
                }

                document.getElementById('btnHideCalc').onclick = function () {
                    calcCardWrapper.classList.add('d-none');
                };

                const btnCopyProposed = document.getElementById('btnCopyProposed');
                if (btnCopyProposed) {
                    if (window.bootstrap && bootstrap.Tooltip) {
                        bootstrap.Tooltip.getOrCreateInstance(btnCopyProposed);
                    }
                    btnCopyProposed.classList.remove('d-none');
                    btnCopyProposed.onclick = async function () {
                        const text = (calc?.proposedVin || '').replace(/\s+/g, '');
                        const original = btnCopyProposed.innerHTML;
                        try {
                            await copyToClipboard(text);
                            btnCopyProposed.innerHTML = '<i class="bi bi-check2 me-1"></i> Copiado';
                            setTimeout(() => btnCopyProposed.innerHTML = original, 1500);
                        } catch (err) {
                            console.error('No se pudo copiar el VIN:', err);
                            btnCopyProposed.innerHTML = '<i class="bi bi-x-lg me-1"></i> Error';
                            setTimeout(() => btnCopyProposed.innerHTML = original, 1500);
                        }
                    };
                }

                resultBox.classList.remove('d-none');
            } catch (err) {
                errorBox.textContent = 'No se pudo contactar al servicio de decodificación. Intenta más tarde.';
                errorBox.classList.remove('d-none');
            } finally {
                spinner.classList.add('d-none');
                btn.disabled = false;
            }
        });
    });
})();
</script>
@endpush
