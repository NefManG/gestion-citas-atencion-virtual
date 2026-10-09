const currentUsername =
    document.getElementById("currentUsername");

const profileUsername =
    document.getElementById("profileUsername");

const profileRoles =
    document.getElementById("profileRoles");

const userAvatar =
    document.getElementById("userAvatar");

const logoutButton =
    document.getElementById("logoutButton");


async function loadCurrentUser() {

    try {

        const response = await fetch(
            "/api/v1/auth/me",
            {
                method: "GET",
                credentials: "same-origin"
            }
        );


        const result = await response.json();


        /*
        |--------------------------------------------------------------------------
        | Si no existe sesión
        |--------------------------------------------------------------------------
        */

        if (
            !response.ok ||
            result.success !== true
        ) {

            window.location.href = "/login";

            return;
        }


        const user = result.data;

        const username =
            user.username ?? "Usuario";

        const roles =
            Array.isArray(user.roles)
                ? user.roles.join(", ")
                : "Usuario";


        currentUsername.textContent =
            username;

        profileUsername.textContent =
            username;

        profileRoles.textContent =
            roles;


        userAvatar.textContent =
            username
                .charAt(0)
                .toUpperCase();

    } catch (error) {

        console.error(
            "No se pudo comprobar la sesión:",
            error
        );

        window.location.href = "/login";

    }

}


async function logout() {

    logoutButton.disabled = true;
    logoutButton.textContent = "Cerrando...";

    try {

        await fetch(
            "/api/v1/auth/logout",
            {
                method: "POST",
                credentials: "same-origin"
            }
        );

    } catch (error) {

        console.error(
            "Error al cerrar sesión:",
            error
        );

    } finally {

        window.location.href = "/login";

    }

}


logoutButton.addEventListener(
    "click",
    logout
);


loadCurrentUser();