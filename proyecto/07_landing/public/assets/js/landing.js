/*
|--------------------------------------------------------------------------
| MENÚ RESPONSIVE
|--------------------------------------------------------------------------
*/

const menuButton =
    document.getElementById("menuButton");

const mainNavigation =
    document.getElementById("mainNavigation");


if (menuButton && mainNavigation) {

    menuButton.addEventListener(
        "click",
        () => {

            const open =
                mainNavigation.classList.toggle(
                    "is-open"
                );


            menuButton.setAttribute(
                "aria-expanded",
                String(open)
            );


            menuButton.setAttribute(
                "aria-label",
                open
                    ? "Cerrar menú"
                    : "Abrir menú"
            );
        }
    );


    mainNavigation
        .querySelectorAll("a")
        .forEach((link) => {

            link.addEventListener(
                "click",
                () => {

                    mainNavigation.classList.remove(
                        "is-open"
                    );


                    menuButton.setAttribute(
                        "aria-expanded",
                        "false"
                    );


                    menuButton.setAttribute(
                        "aria-label",
                        "Abrir menú"
                    );
                }
            );
        });
}


/*
|--------------------------------------------------------------------------
| MOTION CON PROPÓSITO
|--------------------------------------------------------------------------
*/

const reducedMotion =
    window.matchMedia(
        "(prefers-reduced-motion: reduce)"
    ).matches;


const revealElements =
    document.querySelectorAll(
        [
            ".section-heading",
            ".specialty-card",
            ".doctor-card",
            ".care-content",
            ".care-panel",
            ".benefit-card",
            ".final-cta-box",
            ".public-info-container"
        ].join(",")
    );


revealElements.forEach(
    (element) => {

        element.classList.add(
            "reveal"
        );
    }
);


if (reducedMotion) {

    revealElements.forEach(
        (element) => {

            element.classList.add(
                "is-visible"
            );
        }
    );

} else {

    const observer =
        new IntersectionObserver(
            (entries, observerInstance) => {

                entries.forEach(
                    (entry) => {

                        if (!entry.isIntersecting) {
                            return;
                        }


                        entry.target.classList.add(
                            "is-visible"
                        );


                        observerInstance.unobserve(
                            entry.target
                        );
                    }
                );
            },
            {
                threshold: 0.12
            }
        );


    revealElements.forEach(
        (element) => {

            observer.observe(
                element
            );
        }
    );
}

/*
|--------------------------------------------------------------------------
| ACCESIBILIDAD DEL MENÚ
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "keydown",
    (event) => {

        if (
            event.key === "Escape"
            && mainNavigation
            && menuButton
        ) {

            mainNavigation.classList.remove(
                "is-open"
            );

            menuButton.setAttribute(
                "aria-expanded",
                "false"
            );

            menuButton.setAttribute(
                "aria-label",
                "Abrir menú"
            );
        }
    }
);