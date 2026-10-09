<section
    id="medicos"
    class="site-section doctors-section"
>

    <div class="site-container">

        <div class="section-heading">

            <span class="section-eyebrow">
                Profesionales de salud
            </span>

            <h2>
                Conoce a nuestros
                profesionales médicos.
            </h2>

            <p>
                Consulta los profesionales activos
                registrados actualmente en el sistema
                del Hospital Boliviano Español.
            </p>

        </div>


        <?php if (!empty($medicos)): ?>

            <div class="doctors-grid">

                <?php foreach ($medicos as $medico): ?>

                    <?php

                    $nombre =
                        trim(
                            (string) (
                                $medico['nombre_completo']
                                ?? 'Profesional médico'
                            )
                        );


                    $colegiado =
                        trim(
                            (string) (
                                $medico['numero_colegiado']
                                ?? ''
                            )
                        );


                    $especialidadesMedico =
                        is_array(
                            $medico['especialidades']
                            ?? null
                        )
                            ? $medico['especialidades']
                            : [];


                    $partesNombre =
                        preg_split(
                            '/\s+/',
                            $nombre
                        );


                    $iniciales = '';

                    foreach (
                        array_slice(
                            $partesNombre ?: [],
                            0,
                            2
                        )
                        as $parte
                    ) {

                        if ($parte !== '') {

                            $iniciales .=
                                mb_strtoupper(
                                    mb_substr(
                                        $parte,
                                        0,
                                        1
                                    )
                                );
                        }
                    }


                    if ($iniciales === '') {
                        $iniciales = 'Dr';
                    }

                    ?>


                    <article class="doctor-card">


                        <div class="doctor-card-header">

                            <div class="doctor-main-avatar">

                                <?= htmlspecialchars(
                                    $iniciales,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </div>


                            <span class="doctor-status">

                                <span
                                    class="status-indicator"
                                ></span>

                                Activo

                            </span>

                        </div>


                        <div class="doctor-card-body">

                            <span class="doctor-label">
                                Profesional médico
                            </span>


                            <h3>

                                <?= htmlspecialchars(
                                    $nombre,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </h3>


                            <?php if ($colegiado !== ''): ?>

                                <p class="doctor-license">

                                    Colegiado:

                                    <strong>
                                        <?= htmlspecialchars(
                                            $colegiado,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </strong>

                                </p>

                            <?php endif; ?>


                            <div class="doctor-specialties">

                                <?php
                                if (
                                    !empty(
                                        $especialidadesMedico
                                    )
                                ):
                                ?>

                                    <?php
                                    foreach (
                                        $especialidadesMedico
                                        as $especialidad
                                    ):
                                    ?>

                                        <span class="badge">

                                            <?= htmlspecialchars(
                                                $especialidad['nombre']
                                                ?? 'Especialidad',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </span>

                                    <?php endforeach; ?>


                                <?php else: ?>

                                    <span
                                        class="doctor-specialty-empty"
                                    >
                                        Especialidad no asociada
                                        actualmente
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>


                        <div class="doctor-card-footer">

                            <span>
                                Información registrada
                                en el sistema
                            </span>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>


            <div class="data-source-note">

                <span class="status-indicator"></span>

                <span>

                    <?= count($medicos) ?>

                    médico<?= count($medicos) === 1 ? '' : 's' ?>

                    activo<?= count($medicos) === 1 ? '' : 's' ?>

                    consultado<?= count($medicos) === 1 ? '' : 's' ?>

                    desde PostgreSQL.

                </span>

            </div>


        <?php else: ?>

            <div class="empty-state">

                <div class="doctor-main-avatar">
                    Dr
                </div>

                <h3>
                    Profesionales temporalmente no disponibles
                </h3>

                <p>
                    En este momento no fue posible consultar
                    la información de médicos.
                </p>

            </div>

        <?php endif; ?>

    </div>

</section>