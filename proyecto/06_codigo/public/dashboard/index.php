<?php

declare(strict_types=1);

$pageTitle = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= htmlspecialchars($pageTitle) ?> |
        Hospital Boliviano Español
    </title>

    <link
        rel="stylesheet"
        href="/assets/css/app.css"
    >

</head>

<body>

<div class="app-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="sidebar-brand">

            <div class="brand-icon">
                +
            </div>

            <div>
                <strong>
                    Hospital Boliviano Español
                </strong>

                <span>
                    Gestión de Citas
                </span>
            </div>

        </div>


        <nav class="sidebar-nav">

            <a
                href="/dashboard"
                class="nav-link active"
            >
                Inicio
            </a>

            <a
                href="/especialidades"
                class="nav-link"
            >
                Especialidades
            </a>

            <a
                href="/medicos"
                class="nav-link"
            >
                Médicos
            </a>

        </nav>


        <div class="sidebar-footer">

            <button
                id="logoutButton"
                class="logout-button"
                type="button"
            >
                Cerrar sesión
            </button>

        </div>

    </aside>


    <!-- CONTENIDO -->
    <main class="dashboard-main">

        <header class="dashboard-header">

            <div>

                <span class="dashboard-eyebrow">
                    Panel principal
                </span>

                <h1>
                    Bienvenido,
                    <span id="currentUsername">
                        usuario
                    </span>
                </h1>

                <p>
                    Sistema de Gestión de Citas y Atención Virtual
                </p>

            </div>


            <div class="user-profile">

                <div class="user-avatar" id="userAvatar">
                    U
                </div>

                <div>

                    <strong id="profileUsername">
                        Cargando...
                    </strong>

                    <span id="profileRoles">
                        ...
                    </span>

                </div>

            </div>

        </header>


        <section class="dashboard-welcome">

            <div>

                <span class="welcome-label">
                    Hospital Boliviano Español
                </span>

                <h2>
                    Gestión médica organizada,
                    segura y centralizada.
                </h2>

                <p>
                    Desde este panel puedes administrar
                    las especialidades y médicos disponibles
                    en el sistema.
                </p>

            </div>

            <div class="welcome-symbol">
                +
            </div>

        </section>


        <section class="dashboard-section">

            <div class="section-heading">

                <div>
                    <span class="section-label">
                        Administración
                    </span>

                    <h2>
                        Módulos disponibles
                    </h2>
                </div>

            </div>


            <div class="module-grid">

                <!-- ESPECIALIDADES -->
                <article class="module-card">

                    <div class="module-number">
                        01
                    </div>

                    <div class="module-content">

                        <span class="module-category">
                            Gestión médica
                        </span>

                        <h3>
                            Especialidades
                        </h3>

                        <p>
                            Consulta, registra, modifica
                            e inactiva las especialidades
                            médicas disponibles.
                        </p>

                        <a
                            href="/especialidades"
                            class="module-action"
                        >
                            Gestionar especialidades
                            <span>→</span>
                        </a>

                    </div>

                </article>


                <!-- MÉDICOS -->
                <article class="module-card">

                    <div class="module-number">
                        02
                    </div>

                    <div class="module-content">

                        <span class="module-category">
                            Personal médico
                        </span>

                        <h3>
                            Médicos
                        </h3>

                        <p>
                            Administra la información
                            de los médicos registrados
                            en el hospital.
                        </p>

                        <a
                            href="/medicos"
                            class="module-action"
                        >
                            Gestionar médicos
                            <span>→</span>
                        </a>

                    </div>

                </article>

            </div>

        </section>


        <section class="system-status">

            <div class="status-item">

                <span class="status-dot"></span>

                <div>
                    <strong>
                        Sistema disponible
                    </strong>

                    <small>
                        Backend conectado
                    </small>
                </div>

            </div>

            <div class="status-item">

                <span class="status-dot"></span>

                <div>
                    <strong>
                        Sesión activa
                    </strong>

                    <small id="sessionStatus">
                        Verificada
                    </small>
                </div>

            </div>

        </section>

    </main>

</div>


<script src="/assets/js/dashboard.js"></script>

</body>
</html>