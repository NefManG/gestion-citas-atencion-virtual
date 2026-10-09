const API_URL = "/api/v1/especialidades";

const formCard =
    document.getElementById("specialtyFormCard");

const specialtyForm =
    document.getElementById("specialtyForm");

const specialtyId =
    document.getElementById("specialtyId");

const specialtyName =
    document.getElementById("specialtyName");

const specialtyDescription =
    document.getElementById("specialtyDescription");

const formTitle =
    document.getElementById("formTitle");

const formMessage =
    document.getElementById("formMessage");

const tableBody =
    document.getElementById("specialtiesTableBody");

const tableMessage =
    document.getElementById("tableMessage");

const searchInput =
    document.getElementById("searchInput");

const newButton =
    document.getElementById("newButton");

const cancelButton =
    document.getElementById("cancelButton");

const saveButton =
    document.getElementById("saveButton");

const logoutButton =
    document.getElementById("logoutButton");


/*
|--------------------------------------------------------------------------
| SEGURIDAD BÁSICA PARA TEXTO
|--------------------------------------------------------------------------
*/

function escapeHtml(value) {

    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}


/*
|--------------------------------------------------------------------------
| SESIÓN
|--------------------------------------------------------------------------
*/

async function verifySession() {

    try {

        const response =
            await fetch(
                "/api/v1/auth/me",
                {
                    credentials: "same-origin"
                }
            );

        const result =
            await response.json();


        if (
            !response.ok ||
            result.success !== true
        ) {

            window.location.replace("/login");

            return false;
        }

        return true;

    } catch (error) {

        console.error(error);

        window.location.replace("/login");

        return false;
    }
}


/*
|--------------------------------------------------------------------------
| LISTAR
|--------------------------------------------------------------------------
*/

async function loadSpecialties(search = "") {

    tableMessage.textContent =
        "Cargando especialidades...";

    try {

        const url =
            `${API_URL}?limite=100&buscar=${encodeURIComponent(search)}`;

        const response =
            await fetch(
                url,
                {
                    credentials: "same-origin"
                }
            );

        const result =
            await response.json();


        if (
            !response.ok ||
            result.success !== true
        ) {

            tableMessage.textContent =
                "No fue posible cargar las especialidades.";

            return;
        }


        const specialties =
            Array.isArray(result.data)
                ? result.data
                : [];


        renderSpecialties(specialties);

    } catch (error) {

        console.error(error);

        tableMessage.textContent =
            "Error de conexión con el servidor.";
    }
}


/*
|--------------------------------------------------------------------------
| MOSTRAR TABLA
|--------------------------------------------------------------------------
*/

function renderSpecialties(specialties) {

    tableBody.innerHTML = "";


    if (specialties.length === 0) {

        tableMessage.textContent =
            "No existen especialidades para mostrar.";

        return;
    }


    tableMessage.textContent =
        `${specialties.length} especialidad(es) encontrada(s).`;


    specialties.forEach((specialty) => {

        const row =
            document.createElement("tr");


        row.innerHTML = `
            <td>
                ${escapeHtml(specialty.id)}
            </td>

            <td>
                <strong>
                    ${escapeHtml(specialty.nombre)}
                </strong>
            </td>

            <td>
                ${escapeHtml(
                    specialty.descripcion
                    ?? "Sin descripción"
                )}
            </td>

            <td>
                <span class="status-badge active">
                    Activa
                </span>
            </td>

            <td class="table-actions">

                <button
                    type="button"
                    class="table-button edit"
                    data-action="edit"
                    data-id="${escapeHtml(specialty.id)}"
                >
                    Editar
                </button>

                <button
                    type="button"
                    class="table-button delete"
                    data-action="delete"
                    data-id="${escapeHtml(specialty.id)}"
                    data-name="${escapeHtml(specialty.nombre)}"
                >
                    Inactivar
                </button>

            </td>
        `;


        tableBody.appendChild(row);
    });
}


/*
|--------------------------------------------------------------------------
| NUEVO
|--------------------------------------------------------------------------
*/

function openNewForm() {

    specialtyId.value = "";

    specialtyName.value = "";
    specialtyDescription.value = "";

    formTitle.textContent =
        "Nueva especialidad";

    saveButton.textContent =
        "Guardar especialidad";

    formMessage.textContent = "";
    formMessage.className =
        "form-message";

    formCard.classList.remove("hidden");

    specialtyName.focus();
}


/*
|--------------------------------------------------------------------------
| EDITAR
|--------------------------------------------------------------------------
*/

async function editSpecialty(id) {

    try {

        const response =
            await fetch(
                `${API_URL}/${id}`,
                {
                    credentials: "same-origin"
                }
            );

        const result =
            await response.json();


        if (
            !response.ok ||
            result.success !== true
        ) {

            alert(
                result?.error?.message
                ?? "No se pudo consultar la especialidad."
            );

            return;
        }


        const specialty =
            result.data;


        specialtyId.value =
            specialty.id;

        specialtyName.value =
            specialty.nombre ?? "";

        specialtyDescription.value =
            specialty.descripcion ?? "";


        formTitle.textContent =
            "Editar especialidad";

        saveButton.textContent =
            "Guardar cambios";

        formMessage.textContent = "";
        formMessage.className =
            "form-message";


        formCard.classList.remove("hidden");

        specialtyName.focus();


        window.scrollTo({
            top: 0,
            behavior: "smooth"
        });

    } catch (error) {

        console.error(error);

        alert(
            "No se pudo conectar con el servidor."
        );
    }
}


/*
|--------------------------------------------------------------------------
| GUARDAR
|--------------------------------------------------------------------------
*/

specialtyForm.addEventListener(
    "submit",
    async (event) => {

        event.preventDefault();


        const nombre =
            specialtyName.value.trim();

        const descripcion =
            specialtyDescription.value.trim();


        if (nombre === "") {

            showFormMessage(
                "El nombre de la especialidad es obligatorio.",
                "error"
            );

            return;
        }


        const id =
            specialtyId.value;


        const editing =
            id !== "";


        const url =
            editing
                ? `${API_URL}/${id}`
                : API_URL;


        const method =
            editing
                ? "PUT"
                : "POST";


        saveButton.disabled = true;
        saveButton.textContent =
            "Guardando...";


        try {

            const response =
                await fetch(
                    url,
                    {
                        method,

                        credentials:
                            "same-origin",

                        headers: {
                            "Content-Type":
                                "application/json"
                        },

                        body:
                            JSON.stringify({
                                nombre,
                                descripcion:
                                    descripcion || null
                            })
                    }
                );


            const result =
                await response.json();


            if (
                !response.ok ||
                result.success !== true
            ) {

                showFormMessage(
                    result?.error?.message
                    ?? "No se pudo guardar la especialidad.",
                    "error"
                );

                return;
            }


            showFormMessage(
                editing
                    ? "Especialidad actualizada correctamente."
                    : "Especialidad registrada correctamente.",
                "success"
            );


            specialtyId.value = "";
            specialtyName.value = "";
            specialtyDescription.value = "";


            await loadSpecialties(
                searchInput.value.trim()
            );


            setTimeout(() => {

                formCard.classList.add(
                    "hidden"
                );

            }, 700);


        } catch (error) {

            console.error(error);

            showFormMessage(
                "No se pudo conectar con el servidor.",
                "error"
            );

        } finally {

            saveButton.disabled = false;

            saveButton.textContent =
                "Guardar especialidad";
        }
    }
);


/*
|--------------------------------------------------------------------------
| INACTIVAR
|--------------------------------------------------------------------------
*/

async function deactivateSpecialty(
    id,
    name
) {

    const confirmed =
        confirm(
            `¿Deseas inactivar la especialidad "${name}"?`
        );


    if (!confirmed) {
        return;
    }


    try {

        const response =
            await fetch(
                `${API_URL}/${id}`,
                {
                    method: "DELETE",
                    credentials: "same-origin"
                }
            );


        const result =
            await response.json();


        if (
            !response.ok ||
            result.success !== true
        ) {

            alert(
                result?.error?.message
                ?? "No se pudo inactivar la especialidad."
            );

            return;
        }


        alert(
            "Especialidad inactivada correctamente."
        );


        await loadSpecialties(
            searchInput.value.trim()
        );


    } catch (error) {

        console.error(error);

        alert(
            "No se pudo conectar con el servidor."
        );
    }
}


/*
|--------------------------------------------------------------------------
| EVENTOS DE LA TABLA
|--------------------------------------------------------------------------
*/

tableBody.addEventListener(
    "click",
    (event) => {

        const button =
            event.target.closest(
                "button[data-action]"
            );


        if (!button) {
            return;
        }


        const id =
            button.dataset.id;


        if (
            button.dataset.action === "edit"
        ) {

            editSpecialty(id);

            return;
        }


        if (
            button.dataset.action === "delete"
        ) {

            deactivateSpecialty(
                id,
                button.dataset.name
            );
        }
    }
);


/*
|--------------------------------------------------------------------------
| MENSAJES
|--------------------------------------------------------------------------
*/

function showFormMessage(
    message,
    type
) {

    formMessage.textContent =
        message;

    formMessage.className =
        `form-message ${type}`;
}


/*
|--------------------------------------------------------------------------
| BÚSQUEDA
|--------------------------------------------------------------------------
*/

let searchTimer;


searchInput.addEventListener(
    "input",
    () => {

        clearTimeout(searchTimer);

        searchTimer =
            setTimeout(
                () => {

                    loadSpecialties(
                        searchInput.value.trim()
                    );

                },
                300
            );
    }
);


/*
|--------------------------------------------------------------------------
| BOTONES
|--------------------------------------------------------------------------
*/

newButton.addEventListener(
    "click",
    openNewForm
);


cancelButton.addEventListener(
    "click",
    () => {

        formCard.classList.add(
            "hidden"
        );
    }
);


/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/

logoutButton.addEventListener(
    "click",
    async () => {

        try {

            await fetch(
                "/api/v1/auth/logout",
                {
                    method: "POST",
                    credentials: "same-origin"
                }
            );

        } finally {

            window.location.replace(
                "/login"
            );
        }
    }
);


/*
|--------------------------------------------------------------------------
| INICIO
|--------------------------------------------------------------------------
*/

async function initialize() {

    const authenticated =
        await verifySession();


    if (!authenticated) {
        return;
    }


    await loadSpecialties();
}


initialize();