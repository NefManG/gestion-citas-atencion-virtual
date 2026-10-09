<?php

declare(strict_types=1);


/*
|--------------------------------------------------------------------------
| CONFIGURACIÓN
|--------------------------------------------------------------------------
*/

$root = dirname(__DIR__);

$checkpoint = $argv[1] ?? '';


if ($checkpoint === '') {

    echo "Uso:\n";
    echo "php tools/validate_checkpoint.php CP-LAND-16\n";

    exit(1);
}


/*
|--------------------------------------------------------------------------
| AUTOLOAD
|--------------------------------------------------------------------------
*/

spl_autoload_register(
    function (string $class) use ($root): void {

        $prefix = 'App\\';

        if (!str_starts_with($class, $prefix)) {
            return;
        }


        $relativeClass =
            substr(
                $class,
                strlen($prefix)
            );


        $file =
            $root
            . '/app/'
            . str_replace(
                '\\',
                '/',
                $relativeClass
            )
            . '.php';


        if (is_file($file)) {
            require $file;
        }
    }
);


/*
|--------------------------------------------------------------------------
| FUNCIONES
|--------------------------------------------------------------------------
*/

function validationPass(
    string $message
): void {

    echo "[PASSED] {$message}\n";
}


function validationFail(
    string $message
): void {

    echo "[FAILED] {$message}\n";

    exit(1);
}


function validateFileExists(
    string $root,
    string $relativePath
): void {

    $fullPath =
        $root
        . '/'
        . $relativePath;


    if (!is_file($fullPath)) {

        validationFail(
            "No existe: {$relativePath}"
        );
    }


    validationPass(
        "Existe: {$relativePath}"
    );
}


/*
|--------------------------------------------------------------------------
| CP-LAND-16
|--------------------------------------------------------------------------
*/

if ($checkpoint !== 'CP-LAND-16') {

    echo "Checkpoint recibido: {$checkpoint}\n";
    echo "Este validador final se utiliza con CP-LAND-16.\n";

    exit(1);
}


echo "\n";
echo "============================================\n";
echo " VALIDACIÓN FINAL DE LA LANDING\n";
echo " CP-LAND-16\n";
echo "============================================\n\n";


/*
|--------------------------------------------------------------------------
| 1. ARCHIVOS OBLIGATORIOS
|--------------------------------------------------------------------------
*/

echo "1. ESTRUCTURA\n";
echo "--------------------------------------------\n";


$requiredFiles = [

    'public/index.php',

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

    'public/assets/css/variables.css',
    'public/assets/css/base.css',
    'public/assets/css/layout.css',
    'public/assets/css/components.css',
    'public/assets/css/landing.css',
    'public/assets/css/responsive.css',

    'public/assets/js/landing.js',

    'docs/fuentes_imagenes.md',
    'docs/decisiones_visuales.md'
];


foreach (
    $requiredFiles
    as $file
) {

    validateFileExists(
        $root,
        $file
    );
}


echo "\n";


/*
|--------------------------------------------------------------------------
| 2. ARQUITECTURA DE INDEX.PHP
|--------------------------------------------------------------------------
*/

echo "2. ARQUITECTURA\n";
echo "--------------------------------------------\n";


$indexContent =
    file_get_contents(
        $root
        . '/public/index.php'
    );


if ($indexContent === false) {

    validationFail(
        'No se pudo leer public/index.php'
    );
}


/*
 * Index no debe contener consultas SQL.
 */

if (
    preg_match(
        '/\b(SELECT|INSERT|UPDATE|DELETE)\b/i',
        $indexContent
    )
) {

    validationFail(
        'public/index.php contiene SQL.'
    );
}


validationPass(
    'public/index.php no contiene SQL.'
);


/*
 * Index no debe crear PDO directamente.
 */

if (
    preg_match(
        '/new\s+PDO\s*\(/i',
        $indexContent
    )
) {

    validationFail(
        'public/index.php crea PDO directamente.'
    );
}


validationPass(
    'public/index.php no crea PDO directamente.'
);


/*
|--------------------------------------------------------------------------
| 3. SECCIONES
|--------------------------------------------------------------------------
*/

$requiredSections = [

    'hero.php',
    'especialidades.php',
    'medicos.php',
    'atencion.php',
    'beneficios.php',
    'llamada_accion.php',
    'contacto.php'
];


foreach (
    $requiredSections
    as $section
) {

    if (
        !str_contains(
            $indexContent,
            $section
        )
    ) {

        validationFail(
            "index.php no carga {$section}"
        );
    }
}


validationPass(
    'Todas las secciones principales están integradas.'
);


echo "\n";


/*
|--------------------------------------------------------------------------
| 4. LANDING READ ONLY
|--------------------------------------------------------------------------
*/

echo "3. SEGURIDAD Y READ_ONLY\n";
echo "--------------------------------------------\n";


$repositoryFiles = [

    $root
    . '/app/Repositories/LandingEspecialidadRepository.php',

    $root
    . '/app/Repositories/LandingMedicoRepository.php'
];


foreach (
    $repositoryFiles
    as $repositoryFile
) {

    $repositoryContent =
        file_get_contents(
            $repositoryFile
        );


    if ($repositoryContent === false) {

        validationFail(
            'No se pudo leer un Repository.'
        );
    }


    if (
        preg_match(
            '/\b(INSERT|UPDATE|DELETE|DROP|ALTER|TRUNCATE)\b/i',
            $repositoryContent
        )
    ) {

        validationFail(
            'Se detectó una operación de escritura en Landing.'
        );
    }
}


validationPass(
    'Repositories sin operaciones de escritura.'
);

validationPass(
    'Runtime Landing compatible con READ_ONLY.'
);


echo "\n";


/*
|--------------------------------------------------------------------------
| 5. SALIDA SEGURA
|--------------------------------------------------------------------------
*/

$especialidadesView =
    file_get_contents(
        $root
        . '/app/Views/sections/especialidades.php'
    );


$medicosView =
    file_get_contents(
        $root
        . '/app/Views/sections/medicos.php'
    );


if (
    !str_contains(
        (string) $especialidadesView,
        'htmlspecialchars'
    )
) {

    validationFail(
        'Especialidades no utiliza htmlspecialchars.'
    );
}


if (
    !str_contains(
        (string) $medicosView,
        'htmlspecialchars'
    )
) {

    validationFail(
        'Médicos no utiliza htmlspecialchars.'
    );
}


validationPass(
    'Salida dinámica escapada con htmlspecialchars.'
);


echo "\n";


/*
|--------------------------------------------------------------------------
| 6. ACCESIBILIDAD
|--------------------------------------------------------------------------
*/

echo "4. ACCESIBILIDAD\n";
echo "--------------------------------------------\n";


$headerContent =
    file_get_contents(
        $root
        . '/app/Views/layouts/header.php'
    );


if (
    !str_contains(
        (string) $headerContent,
        'lang="es"'
    )
) {

    validationFail(
        'Falta lang="es".'
    );
}


validationPass(
    'Idioma español definido.'
);


if (
    !str_contains(
        (string) $headerContent,
        'menuButton'
    )
    ||
    !str_contains(
        (string) $headerContent,
        'aria-expanded'
    )
    ||
    !str_contains(
        (string) $headerContent,
        'aria-controls'
    )
) {

    validationFail(
        'Faltan atributos accesibles del menú.'
    );
}


validationPass(
    'Menú responsive con atributos ARIA.'
);


if (
    !str_contains(
        (string) $headerContent,
        'skip-link'
    )
) {

    validationFail(
        'No se encontró skip-link.'
    );
}


validationPass(
    'Skip-link implementado.'
);


if (
    !str_contains(
        (string) $headerContent,
        'contenido-principal'
    )
) {

    validationFail(
        'No se encontró contenido-principal.'
    );
}


validationPass(
    'Contenido principal identificable.'
);


echo "\n";


/*
|--------------------------------------------------------------------------
| 7. RESPONSIVE Y MOTION
|--------------------------------------------------------------------------
*/

echo "5. RESPONSIVE Y MOTION\n";
echo "--------------------------------------------\n";


$responsiveContent =
    file_get_contents(
        $root
        . '/public/assets/css/responsive.css'
    );


if (
    !str_contains(
        (string) $responsiveContent,
        '@media'
    )
) {

    validationFail(
        'No se encontraron media queries.'
    );
}


validationPass(
    'Media queries presentes.'
);


if (
    !str_contains(
        (string) $responsiveContent,
        'prefers-reduced-motion'
    )
) {

    validationFail(
        'No se encontró prefers-reduced-motion.'
    );
}


validationPass(
    'prefers-reduced-motion implementado.'
);


$javascriptContent =
    file_get_contents(
        $root
        . '/public/assets/js/landing.js'
    );


if (
    !str_contains(
        (string) $javascriptContent,
        'IntersectionObserver'
    )
) {

    validationFail(
        'No se encontró IntersectionObserver.'
    );
}


validationPass(
    'Motion mediante IntersectionObserver.'
);


if (
    !str_contains(
        (string) $javascriptContent,
        'Escape'
    )
) {

    validationFail(
        'El menú no contempla tecla Escape.'
    );
}


validationPass(
    'Cierre de menú con Escape.'
);


echo "\n";


/*
|--------------------------------------------------------------------------
| 8. PERFORMANCE
|--------------------------------------------------------------------------
*/

echo "6. PERFORMANCE\n";
echo "--------------------------------------------\n";


$footerContent =
    file_get_contents(
        $root
        . '/app/Views/layouts/footer.php'
    );


if (
    !str_contains(
        (string) $footerContent,
        'landing.js'
    )
) {

    validationFail(
        'landing.js no está cargado.'
    );
}


validationPass(
    'JavaScript externo cargado.'
);


if (
    !str_contains(
        (string) $footerContent,
        'defer'
    )
) {

    validationFail(
        'landing.js no utiliza defer.'
    );
}


validationPass(
    'JavaScript cargado con defer.'
);


echo "\n";


/*
|--------------------------------------------------------------------------
| 9. CONEXIÓN REAL CON DATOS
|--------------------------------------------------------------------------
*/

echo "7. DATOS REALES\n";
echo "--------------------------------------------\n";


try {

    $service =
        new \App\Services\LandingHospitalService();


    $especialidades =
        $service->getEspecialidades();


    $medicos =
        $service->getMedicos();


    if (!is_array($especialidades)) {

        validationFail(
            'Especialidades no retornó un array.'
        );
    }


    if (!is_array($medicos)) {

        validationFail(
            'Médicos no retornó un array.'
        );
    }


    validationPass(
        'LandingHospitalService conectado correctamente.'
    );


    echo
        "Especialidades activas consultadas: "
        . count($especialidades)
        . "\n";


    echo
        "Médicos activos consultados: "
        . count($medicos)
        . "\n";


} catch (Throwable $exception) {

    validationFail(
        'Error consultando PostgreSQL: '
        . $exception->getMessage()
    );
}


echo "\n";


/*
|--------------------------------------------------------------------------
| RESULTADO
|--------------------------------------------------------------------------
*/

echo "============================================\n";
echo " CP-LAND-16: PASSED\n";
echo " LANDING VALIDADA CORRECTAMENTE\n";
echo "============================================\n";

exit(0);