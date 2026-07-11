<?php

return [
    /*
     * Correo del administrador que recibe copia oculta (BCC) de las alertas
     * de robo/fraude/recuperado.
     */
    'admin_email' => env('ADMIN_EMAIL', 'admin@vintrack.com.mx'),

    /*
     * Umbral de saldo por debajo del cual se envia el correo de "creditos bajos".
     */
    'low_credit_threshold' => (float) env('CREDITOS_MIN_ALERTA_GENERAL', 5),
];
