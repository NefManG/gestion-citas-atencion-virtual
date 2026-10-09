const loginForm = document.getElementById("loginForm");

const credentialInput = document.getElementById("credential");
const passwordInput = document.getElementById("password");

const credentialError = document.getElementById("credentialError");
const passwordError = document.getElementById("passwordError");

const loginMessage = document.getElementById("loginMessage");
const loginButton = document.getElementById("loginButton");


function clearMessages() {
    credentialError.textContent = "";
    passwordError.textContent = "";

    loginMessage.textContent = "";
    loginMessage.className = "form-message";
}


function showMessage(message, type) {
    loginMessage.textContent = message;
    loginMessage.className = `form-message ${type}`;
}


function validateForm() {

    let valid = true;

    const credential = credentialInput.value.trim();
    const password = passwordInput.value;

    if (credential === "") {
        credentialError.textContent =
            "Ingresa tu usuario o correo electrónico.";

        valid = false;
    }

    if (password === "") {
        passwordError.textContent =
            "Ingresa tu contraseña.";

        valid = false;
    }

    return valid;
}


loginForm.addEventListener("submit", async (event) => {

    event.preventDefault();

    clearMessages();

    if (!validateForm()) {
        return;
    }

    loginButton.disabled = true;
    loginButton.textContent = "Verificando...";

    try {

        const response = await fetch(
            "/api/v1/auth/login",
            {
                method: "POST",

                credentials: "same-origin",

                headers: {
                    "Content-Type": "application/json"
                },

                body: JSON.stringify({
                    credential: credentialInput.value.trim(),
                    password: passwordInput.value
                })
            }
        );


        const result = await response.json();


        if (!response.ok || result.success !== true) {

            const message =
                result?.error?.message
                ?? "No fue posible iniciar sesión.";

            showMessage(message, "error");

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Comprobar que la sesión realmente quedó creada
        |--------------------------------------------------------------------------
        */

        const sessionResponse = await fetch(
            "/api/v1/auth/me",
            {
                method: "GET",
                credentials: "same-origin"
            }
        );


        const sessionResult = await sessionResponse.json();


        if (
            !sessionResponse.ok ||
            sessionResult.success !== true
        ) {

            showMessage(
                "El usuario fue validado, pero no fue posible comprobar la sesión.",
                "error"
            );

            return;
        }


        const username =
            sessionResult.data?.username
            ?? result.data?.username
            ?? "usuario";


        showMessage(
            `Autenticación correcta. Bienvenido, ${username}.`,
            "success"
        );

        window.location.replace("/dashboard");


    } catch (error) {

        console.error(error);

        showMessage(
            "No se pudo conectar con el servidor.",
            "error"
        );

    } finally {

        loginButton.disabled = false;
        loginButton.textContent = "Ingresar al sistema";

    }

});