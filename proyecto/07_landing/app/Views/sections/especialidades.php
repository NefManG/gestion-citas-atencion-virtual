<section
    id="especialidades"
    class="site-section specialties-section"
>

    <div class="site-container">

        <div class="section-heading">

            <span class="section-eyebrow">
                Atención especializada
            </span>

            <h2>
                Encuentra el área médica
                que necesitas.
            </h2>

            <p>
                Consulta las especialidades activas
                registradas actualmente en el sistema
                del Hospital Boliviano Español.
            </p>

        </div>


        <?php if (!empty($especialidades)): ?>

            <div class="specialties-grid">

                <?php
                foreach (
                    $especialidades
                    as $index => $especialidad
                ):
                ?>

                    <article class="specialty-card">

                        <div class="specialty-card-top">

                            <span class="specialty-icon">
                                +
                            </span>

                            <span class="specialty-number">
                                <?= str_pad(
                                    (string) ($index + 1),
                                    2,
                                    '0',
                                    STR_PAD_LEFT
                                ) ?>
                            </span>

                        </div>


                        <h3>
                            <?= htmlspecialchars(
                                $especialidad['nombre']
                                ?? 'Especialidad médica',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </h3>


                        <p>
                            <?php
                            $descripcion =
                                trim(
                                    (string) (
                                        $especialidad['descripcion']
                                        ?? ''
                                    )
                                );
                            ?>

                            <?= htmlspecialchars(
                                $descripcion !== ''
                                    ? $descripcion
                                    : 'Especialidad médica disponible en el sistema hospitalario.',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>


                        <div class="specialty-footer">

                            <span class="badge">
                                Disponible
                            </span>

                            <a
                                href="#medicos"
                                class="specialty-link"
                            >
                                Ver profesionales
                                <span aria-hidden="true">
                                    →
                                </span>
                            </a>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>


            <div class="data-source-note">

                <span class="status-indicator"></span>

                <span>
                    <?= count($especialidades) ?>
                    especialidad<?= count($especialidades) === 1 ? '' : 'es' ?>
                    activa<?= count($especialidades) === 1 ? '' : 's' ?>
                    consultada<?= count($especialidades) === 1 ? '' : 's' ?>
                    desde el sistema.
                </span>

            </div>


        <?php else: ?>

            <div class="empty-state">

                <span class="empty-state-icon">
                    +
                </span>

                <h3>
                    Información temporalmente no disponible
                </h3>

                <p>
                    En este momento no fue posible
                    consultar las especialidades médicas.
                </p>

            </div>

        <?php endif; ?>

    </div>

</section>