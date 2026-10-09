<?php

declare(strict_types=1);

$pageTitle = 'Especialidades';
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
                <strong>Hospital Boliviano Español</strong>
                <span>Gestión de Citas</span>
            </div>

        </div>

        <nav class="sidebar-nav">

            <a href="/dashboard" class="nav-link">
                Inicio
            </a>

            <a href="/especialidades" class="nav-link active">
                Especialidades
            </a>

            <a href="/medicos" class="nav-link">
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
                    Gestión médica
                </span>

                <h1>Especialidades</h1>

                <p>
                    Administra las especialidades médicas
                    disponibles en el hospital.
                </p>

            </div>

            <button
                id="newButton"
                class="button-primary management-new-button"
                type="button"
            >
                + Nueva especialidad
            </button>

        </header>


        <section
            id="specialtyFormCard"
            class="management-form-card hidden"
        >

            <div class="management-form-header">

                <div>
                    <span class="section-label">
                        Formulario
                    </span>

                    <h2 id="formTitle">
                        Nueva especialidad
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


            <form id="specialtyForm">

                <input
                    type="hidden"
                    id="specialtyId"
                >


                <div class="management-form-grid">

                    <div class="form-group">

                        <label for="specialtyName">
                            Nombre *
                        </label>

                        <input
                            type="text"
                            id="specialtyName"
                            maxlength="255"
                            placeholder="Ej. Cardiología"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="specialtyDescription">
                            Descripción
                        </label>

                        <input
                            type="text"
                            id="specialtyDescription"
                            maxlength="1024"
                            placeholder="Descripción de la especialidad"
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
                        Guardar especialidad
                    </button>

                </div>

            </form>

        </section>


        <section class="management-card">

            <div class="management-toolbar">

                <div>

                    <span class="section-label">
                        Registros
                    </span>

                    <h2>
                        Especialidades activas
                    </h2>

                </div>


                <input
                    type="search"
                    id="searchInput"
                    class="search-input"
                    placeholder="Buscar especialidad..."
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
                        <th>Especialidad</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>

                    </thead>

                    <tbody id="specialtiesTableBody">
                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

<script src="/assets/js/especialidades.js"></script>

</body>

</html>