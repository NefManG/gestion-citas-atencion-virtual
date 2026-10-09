<?php

declare(strict_types=1);

return [

    'name' =>
        'Hospital Boliviano Español',

    'system' =>
        'Sistema de Gestión de Citas y Atención Virtual',

    'environment' =>
        getenv('APP_ENV')
        ?: 'development',

    'debug' =>
        filter_var(
            getenv('APP_DEBUG')
            ?: false,
            FILTER_VALIDATE_BOOL
        ),

    'timezone' =>
        getenv('APP_TIMEZONE')
        ?: 'America/La_Paz',

    'encoding' =>
        'UTF-8'
];