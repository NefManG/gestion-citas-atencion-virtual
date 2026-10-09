<?php

declare(strict_types=1);

$pageTitle = 'Médicos';
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

    <aside class="sidebar">

        <div class="sidebar-brand">

            <div class="brand-icon">+</div>

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
                class="nav-link"
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
                class="nav-link active"
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


    <main class="dashboard-main">

        <header class="management-header">

            <div>

                <span class="dashboard-eyebrow">
                    Personal médico
                </span>

                <h1>
                    Médicos
                </h1>

                <p>
                    Administra los médicos registrados
                    en el Hospital Boliviano Español.
                </p>

            </div>


            <button
                id="newButton"
                class="button-primary management-new-button"
                type="button"
            >
                + Nuevo médico
            </button>

        </header>


        <!-- FORMULARIO -->
        <section
            id="doctorFormCard"
            class="management-form-card hidden"
        >

            <div class="management-form-header">

                <div>

                    <span class="section-label">
                        Formulario
                    </span>

                    <h2 id="formTitle">
                        Nuevo médico
                    </h2>

                </div>


                <button
                    id="cancelButton"
                    class="button-secondary"
                    type="button"
                >
                    Cancelar
                </button>

            </div>


            <form id="doctorForm">

                <input
                    type="hidden"
                    id="doctorId"
                >


                <div class="management-form-grid">


                    <div class="form-group">

                        <label for="doctorName">
                            Nombre completo *
                        </label>

                        <input
                            type="text"
                            id="doctorName"
                            maxlength="200"
                            placeholder="Ej. Dr. Juan Pérez"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="doctorLicense">
                            Número de colegiado *
                        </label>

                        <input
                            type="text"
                            id="doctorLicense"
                            maxlength="30"
                            placeholder="Ej. MED-10258"
                            required
                        >

                    </div>

                </div>


                <div
                    id="formMessage"
                    class="form-message"
                    role="alert"
                ></div>


                <div class="management-form-actions">

                    <button
                        id="saveButton"
                        class="button-primary"
                        type="submit"
                    >
                        Guardar médico
                    </button>

                </div>

            </form>

        </section>


        <!-- LISTA -->
        <section class="management-card">

            <div class="management-toolbar">

                <div>

                    <span class="section-label">
                        Registros
                    </span>

                    <h2>
                        Médicos activos
                    </h2>

                </div>


                <input
                    type="search"
                    id="searchInput"
                    class="search-input"
                    placeholder="Buscar médico..."
                >

            </div>


            <div
                id="tableMessage"
                class="table-message"
            >
                Cargando...
            </div>


            <div class="table-wrapper">

                <table class="management-table">

                    <thead>

                    <tr>
                        <th>ID</th>
                        <th>Médico</th>
                        <th>Colegiado</th>
                        <th>Especialidades</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>

                    </thead>


                    <tbody id="doctorsTableBody">
                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>


<script src="/assets/js/medicos.js"></script>

</body>

</html>