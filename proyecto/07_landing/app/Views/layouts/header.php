<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Sistema de Gestión de Citas y Atención Virtual del Hospital Boliviano Español."
    >

    <title>
        <?= htmlspecialchars(
            $landingConfig['title'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </title>

    <link
        rel="stylesheet"
        href="/assets/css/variables.css"
    >

    <link
        rel="stylesheet"
        href="/assets/css/base.css"
    >

    <link
        rel="stylesheet"
        href="/assets/css/layout.css"
    >

    <link
        rel="stylesheet"
        href="/assets/css/components.css"
    >

    <link
        rel="stylesheet"
        href="/assets/css/landing.css"
    >

    <link
        rel="stylesheet"
        href="/assets/css/responsive.css"
    >

</head>

<body>
<a
    href="#contenido-principal"
    class="skip-link"
>
    Saltar al contenido principal
</a>
<header class="site-header">

    <div class="site-container header-container">

        <a
            href="#inicio"
            class="site-brand"
            aria-label="Hospital Boliviano Español"
        >

            <span class="brand-symbol">
                +
            </span>

            <span class="brand-copy">

                <strong>
                    Hospital Boliviano Español
                </strong>

                <small>
                    Gestión de Citas y Atención Virtual
                </small>

            </span>

        </a>


        <button
            id="menuButton"
            class="menu-button"
            type="button"
            aria-label="Abrir menú"
            aria-expanded="false"
            aria-controls="mainNavigation"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>


        <nav
            id="mainNavigation"
            class="site-navigation"
            aria-label="Navegación principal"
        >

            <a href="#inicio">
                Inicio
            </a>

            <a href="#especialidades">
                Especialidades
            </a>

            <a href="#medicos">
                Médicos
            </a>

            <a href="#atencion">
                Atención
            </a>

        </nav>


        <a
            href="#informacion"
            class="button button--primary header-action"
        >
            Ver especialidades
        </a>

    </div>

</header>

<main id="contenido-principal">