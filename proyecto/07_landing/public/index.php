<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Configuración
|--------------------------------------------------------------------------
*/

$appConfig =
    require dirname(__DIR__)
    . '/config/app.php';

$landingConfig =
    require dirname(__DIR__)
    . '/config/landing.php';


/*
|--------------------------------------------------------------------------
| Autoload simple
|--------------------------------------------------------------------------
*/

spl_autoload_register(
    function (string $class): void {

        $prefix = 'App\\';

        $baseDir =
            dirname(__DIR__)
            . '/app/';


        if (
            !str_starts_with(
                $class,
                $prefix
            )
        ) {
            return;
        }


        $relativeClass =
            substr(
                $class,
                strlen($prefix)
            );


        $file =
            $baseDir
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
| Datos de la Landing
|--------------------------------------------------------------------------
*/

$especialidades = [];
$medicos = [];


try {

    $landingService =
        new \App\Services\LandingHospitalService();


    $especialidades =
        $landingService->getEspecialidades();


    $medicos =
        $landingService->getMedicos();


} catch (Throwable $exception) {

    $especialidades = [];
    $medicos = [];
}



/*
|--------------------------------------------------------------------------
| Render
|--------------------------------------------------------------------------
*/

require dirname(__DIR__)
    . '/app/Views/layouts/header.php';

require dirname(__DIR__)
    . '/app/Views/sections/hero.php';

require dirname(__DIR__)
    . '/app/Views/sections/especialidades.php';

require dirname(__DIR__)
    . '/app/Views/sections/medicos.php';

require dirname(__DIR__)
    . '/app/Views/sections/atencion.php';

require dirname(__DIR__)
    . '/app/Views/sections/beneficios.php';

require dirname(__DIR__)
    . '/app/Views/sections/llamada_accion.php';

require dirname(__DIR__)
    . '/app/Views/sections/contacto.php';

require dirname(__DIR__)
    . '/app/Views/layouts/footer.php';
?>
