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

    /*
     * Número de WhatsApp del administrador para mensajes de validación de registro.
     */
    'admin_whatsapp' => env('ADMIN_WHATSAPP', '0000000000'),

    /*
     * Correo del departamento de ventas para solicitudes de compra única.
     */
    'ventas_email' => env('VENTAS_EMAIL', 'ventas@vintrack.com.mx'),

    /*
     * Correo de contacto para mensajes del formulario público.
     */
    'contact_email' => env('CONTACT_EMAIL', 'info@vintrack.com.mx'),
];
