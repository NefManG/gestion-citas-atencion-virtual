const API_URL = "/api/v1/medicos";

const formCard =
    document.getElementById("doctorFormCard");

const doctorForm =
    document.getElementById("doctorForm");

const doctorId =
    document.getElementById("doctorId");

const doctorName =
    document.getElementById("doctorName");

const doctorLicense =
    document.getElementById("doctorLicense");

const formTitle =
    document.getElementById("formTitle");

const formMessage =
    document.getElementById("formMessage");

const tableBody =
    document.getElementById("doctorsTableBody");

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

let doctors = [];


/*
|--------------------------------------------------------------------------
| ESCAPAR HTML
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
| VERIFICAR SESIÓN
|--------------------------------------------------------------------------
*/

async function verifySession() {

    try {

        const response = await fetch(
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
| CARGAR MÉDICOS
|--------------------------------------------------------------------------
*/

async function loadDoctors() {

    tableMessage.textContent =
        "Cargando médicos...";


    try {

        const response =
            await fetch(
                API_URL,
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
                "No fue posible cargar los médicos.";

            return;
        }


        doctors =
            Array.isArray(result.data)
                ? result.data
                : [];


        filterDoctors();

    } catch (error) {

        console.error(error);

        tableMessage.textContent =
            "Error de conexión con el servidor.";
    }
}


/*
|--------------------------------------------------------------------------
| FILTRAR
|--------------------------------------------------------------------------
*/

function filterDoctors() {

    const search =
        searchInput.value
            .trim()
            .toLowerCase();


    if (search === "") {

        renderDoctors(doctors);

        return;
    }


    const filtered =
        doctors.filter((doctor) => {

            const name =
                String(
                    doctor.nombre_completo ?? ""
                ).toLowerCase();

            const license =
                String(
                    doctor.numero_colegiado ?? ""
                ).toLowerCase();


            const specialties =
                Array.isArray(
                    doctor.especialidades
                )
                    ? doctor.especialidades
                        .map(
                            specialty =>
                                specialty.nombre ?? ""
                        )
                        .join(" ")
                        .toLowerCase()
                    : "";


            return (
                name.includes(search) ||
                license.includes(search) ||
                specialties.includes(search)
            );
        });


    renderDoctors(filtered);
}


/*
|--------------------------------------------------------------------------
| RENDERIZAR
|--------------------------------------------------------------------------
*/

function renderDoctors(list) {

    tableBody.innerHTML = "";


    if (list.length === 0) {

        tableMessage.textContent =
            "No existen médicos para mostrar.";

        return;
    }


    tableMessage.textContent =
        `${list.length} médico(s) encontrado(s).`;


    list.forEach((doctor) => {

        const specialties =
            Array.isArray(
                doctor.especialidades
            )
                ? doctor.especialidades
                : [];


        let specialtiesHtml;


        if (specialties.length === 0) {

            specialtiesHtml = `
                <span class="specialty-empty">
                    Sin especialidad asociada
                </span>
            `;

        } else {

            specialtiesHtml =
                specialties
                    .map(
                        specialty => `
                            <span class="specialty-chip">
                                ${escapeHtml(
                                    specialty.nombre
                                )}
                            </span>
                        `
                    )
                    .join("");
        }


        const row =
            document.createElement("tr");


        row.innerHTML = `

            <td>
                ${escapeHtml(
                    doctor.id_medico
                )}
            </td>


            <td>

                <strong>
                    ${escapeHtml(
                        doctor.nombre_completo
                    )}
                </strong>

            </td>


            <td>

                ${escapeHtml(
                    doctor.numero_colegiado
                )}

            </td>


            <td>

                <div class="specialty-list">
                    ${specialtiesHtml}
                </div>

            </td>


            <td>

                <span class="status-badge active">
                    Activo
                </span>

            </td>


            <td class="table-actions">

                <button
                    type="button"
                    class="table-button edit"
                    data-action="edit"
                    data-id="${escapeHtml(
                        doctor.id_medico
                    )}"
                >
                    Editar
                </button>


                <button
                    type="button"
                    class="table-button delete"
                    data-action="delete"
                    data-id="${escapeHtml(
                        doctor.id_medico
                    )}"
                    data-name="${escapeHtml(
                        doctor.nombre_completo
                    )}"
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
| NUEVO MÉDICO
|--------------------------------------------------------------------------
*/

function openNewForm() {

    doctorId.value = "";

    doctorName.value = "";

    doctorLicense.value = "";


    formTitle.textContent =
        "Nuevo médico";


    saveButton.textContent =
        "Guardar médico";


    formMessage.textContent = "";

    formMessage.className =
        "form-message";


    formCard.classList.remove(
        "hidden"
    );


    doctorName.focus();
}


/*
|--------------------------------------------------------------------------
| EDITAR
|--------------------------------------------------------------------------
*/

async function editDoctor(id) {

    try {

        const response =
            await fetch(
                `${API_URL}/${id}`,
                {
                    credentials:
                        "same-origin"
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
                ?? "No se pudo consultar el médico."
            );

            return;
        }


        const doctor =
            result.data;


        doctorId.value =
            doctor.id_medico;


        doctorName.value =
            doctor.nombre_completo ?? "";


        doctorLicense.value =
            doctor.numero_colegiado ?? "";


        formTitle.textContent =
            "Editar médico";


        saveButton.textContent =
            "Guardar cambios";


        formMessage.textContent = "";

        formMessage.className =
            "form-message";


        formCard.classList.remove(
            "hidden"
        );


        doctorName.focus();


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

doctorForm.addEventListener(
    "submit",
    async (event) => {

        event.preventDefault();


        const nombreCompleto =
            doctorName.value.trim();


        const numeroColegiado =
            doctorLicense.value.trim();


        if (nombreCompleto === "") {

            showFormMessage(
                "El nombre completo es obligatorio.",
                "error"
            );

            return;
        }


        if (numeroColegiado === "") {

            showFormMessage(
                "El número de colegiado es obligatorio.",
                "error"
            );

            return;
        }


        const id =
            doctorId.value;


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


        saveButton.disabled =
            true;


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
                                nombre_completo:
                                    nombreCompleto,

                                numero_colegiado:
                                    numeroColegiado
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
                        ?? "No se pudo guardar el médico.",
                    "error"
                );

                return;
            }


            showFormMessage(

                editing
                    ? "Médico actualizado correctamente."
                    : "Médico registrado correctamente.",

                "success"
            );


            await loadDoctors();


            setTimeout(
                () => {

                    formCard.classList.add(
                        "hidden"
                    );

                },
                700
            );


        } catch (error) {

            console.error(error);


            showFormMessage(
                "No se pudo conectar con el servidor.",
                "error"
            );


        } finally {

            saveButton.disabled =
                false;


            saveButton.textContent =
                "Guardar médico";
        }
    }
);


/*
|--------------------------------------------------------------------------
| INACTIVAR MÉDICO
|--------------------------------------------------------------------------
*/

async function deactivateDoctor(
    id,
    name
) {

    const confirmed =
        confirm(
            `¿Deseas inactivar al médico "${name}"?`
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

                    credentials:
                        "same-origin"
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
                    ?? "No se pudo inactivar el médico."
            );

            return;
        }


        alert(
            "Médico inactivado correctamente."
        );


        await loadDoctors();


    } catch (error) {

        console.error(error);


        alert(
            "No se pudo conectar con el servidor."
        );
    }
}


/*
|--------------------------------------------------------------------------
| EVENTOS TABLA
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
            button.dataset.action ===
            "edit"
        ) {

            editDoctor(id);

            return;
        }


        if (
            button.dataset.action ===
            "delete"
        ) {

            deactivateDoctor(
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

searchInput.addEventListener(
    "input",
    filterDoctors
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
| CERRAR SESIÓN
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

                    credentials:
                        "same-origin"
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
| INICIALIZACIÓN
|--------------------------------------------------------------------------
*/

async function initialize() {

    const authenticated =
        await verifySession();


    if (!authenticated) {
        return;
    }


    await loadDoctors();
}


initialize();