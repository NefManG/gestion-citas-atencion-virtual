<?php

declare(strict_types=1);

$pageTitle = 'Iniciar sesión';
?>
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
        content="Sistema de Gestión de Citas y Atención Virtual del Hospital Boliviano Español"
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

<main class="auth-page">

    <section class="auth-information">

        <div class="auth-brand">
            <div class="brand-icon">
                +
            </div>

            <div>
                <span class="brand-name">
                    Hospital Boliviano Español
                </span>

                <span class="brand-system">
                    Sistema de Gestión de Citas
                </span>
            </div>
        </div>

        <div class="auth-description">

            <span class="auth-label">
                Atención médica
            </span>

            <h1>
                Gestionamos la atención de nuestros pacientes
                de forma segura y organizada.
            </h1>

            <p>
                Accede al sistema para administrar especialidades,
                médicos y los servicios relacionados con la gestión
                de citas médicas.
            </p>

            <div class="auth-features">

                <div class="feature-item">
                    <span class="feature-number">01</span>

                    <div>
                        <strong>Información centralizada</strong>
                        <p>
                            Gestión organizada de los recursos
                            médicos del hospital.
                        </p>
                    </div>
                </div>

                <div class="feature-item">
                    <span class="feature-number">02</span>

                    <div>
                        <strong>Acceso seguro</strong>
                        <p>
                            Autenticación y control de usuarios
                            mediante el Backend del sistema.
                        </p>
                    </div>
                </div>

            </div>

        </div>

    </section>


    <section class="auth-form-section">

        <div class="login-card">

            <div class="login-header">

                <span class="login-eyebrow">
                    Bienvenido
                </span>

                <h2>Iniciar sesión</h2>

                <p>
                    Ingresa tus credenciales para acceder
                    al sistema.
                </p>

            </div>


            <form
                id="loginForm"
                class="login-form"
                novalidate
            >

                <div class="form-group">

                    <label for="credential">
                        Usuario o correo electrónico
                    </label>

                    <input
                        type="text"
                        id="credential"
                        name="credential"
                        autocomplete="username"
                        placeholder="Ingresa tu usuario"
                        required
                    >

                    <span
                        id="credentialError"
                        class="field-error"
                    ></span>

                </div>


                <div class="form-group">

                    <label for="password">
                        Contraseña
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        placeholder="Ingresa tu contraseña"
                        required
                    >

                    <span
                        id="passwordError"
                        class="field-error"
                    ></span>

                </div>


                <div
                    id="loginMessage"
                    class="form-message"
                    role="alert"
                    aria-live="polite"
                ></div>


                <button
                    type="submit"
                    id="loginButton"
                    class="button-primary"
                >
                    Ingresar al sistema
                </button>

            </form>


            <div class="login-footer">
                Sistema de Gestión de Citas y Atención Virtual
            </div>

        </div>

    </section>

</main>

<script src="/assets/js/login.js"></script>

</body>
</html>