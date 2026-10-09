<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$errors = [];

$required = [

    'app/Core/Database.php',

    'app/Repositories/LandingEspecialidadRepository.php',
    'app/Repositories/LandingMedicoRepository.php',

    'app/Services/LandingHospitalService.php',

    'app/Views/layouts/header.php',
    'app/Views/layouts/footer.php',

    'app/Views/sections/hero.php',
    'app/Views/sections/especialidades.php',
    'app/Views/sections/medicos.php',
    'app/Views/sections/atencion.php',
    'app/Views/sections/beneficios.php',
    'app/Views/sections/llamada_accion.php',
    'app/Views/sections/contacto.php',

    'config/app.php',
    'config/database.php',
    'config/landing.php',

    'public/index.php',

    'public/assets/css/variables.css',
    'public/assets/css/base.css',
    'public/assets/css/layout.css',
    'public/assets/css/components.css',
    'public/assets/css/landing.css',
    'public/assets/css/responsive.css',

    'public/assets/js/landing.js',

    'docs/brief_landing.md',
    'docs/fuentes_imagenes.md',
    'docs/decisiones_visuales.md',
    'docs/validacion_final.md',

    'tests/results',

    'tools/validate_checkpoint.php'
];


foreach ($required as $relativePath) {

    $path =
        $root
        . DIRECTORY_SEPARATOR
        . str_replace(
            '/',
            DIRECTORY_SEPARATOR,
            $relativePath
        );


    if (!file_exists($path)) {

        $errors[] =
            "Falta: {$relativePath}";
    }
}


/*
|--------------------------------------------------------------------------
| Comprobaciones arquitectónicas
|--------------------------------------------------------------------------
*/

$index =
    $root . '/public/index.php';


if (is_file($index)) {

    $content =
        file_get_contents($index) ?: '';


    if (
        preg_match(
            '/\b(SELECT|INSERT|UPDATE|DELETE)\b/i',
            $content
        )
    ) {

        $errors[] =
            'public/index.php no puede contener SQL.';
    }


    if (
        preg_match(
            '/new\s+PDO\s*\(/i',
            $content
        )
    ) {

        $errors[] =
            'public/index.php no puede crear PDO directamente.';
    }
}


/*
|--------------------------------------------------------------------------
| Resultado
|--------------------------------------------------------------------------
*/

if ($errors) {

    fwrite(
        STDERR,
        "STRUCTURE: FAILED\n- "
        . implode(
            "\n- ",
            $errors
        )
        . "\n"
    );

    exit(1);
}


echo "STRUCTURE: PASSED\n";

exit(0);