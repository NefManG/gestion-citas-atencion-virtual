<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class LandingMedicoRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo =
            Database::getInstance()->getPdo();
    }


    /**
     * Obtiene únicamente médicos activos.
     *
     * Incluye las especialidades activas
     * que estén realmente asociadas.
     */
    public function getAllActive(): array
    {
        $sql = '
            SELECT
                m.id_medico,
                m.nombre_completo,
                m.numero_colegiado,

                COALESCE(
                    (
                        SELECT json_agg(
                            json_build_object(
                                \'id_especialidad\',
                                e.id_especialidad,

                                \'nombre\',
                                e.nombre
                            )
                            ORDER BY e.nombre
                        )

                        FROM medico_especialidad me

                        INNER JOIN especialidad e
                            ON e.id_especialidad =
                               me.id_especialidad

                        WHERE me.id_medico =
                              m.id_medico

                          AND e.activo = true
                    ),

                    \'[]\'::json
                ) AS especialidades

            FROM medico m

            WHERE m.activo = true

            ORDER BY
                m.nombre_completo ASC,
                m.id_medico ASC
        ';


        $statement =
            $this->pdo->prepare($sql);


        $statement->execute();


        $medicos =
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );


        foreach ($medicos as &$medico) {

            $medico['id_medico'] =
                (int) $medico['id_medico'];


            if (
                is_string(
                    $medico['especialidades']
                )
            ) {

                $medico['especialidades'] =
                    json_decode(
                        $medico['especialidades'],
                        true
                    ) ?: [];
            }
        }

        unset($medico);


        return $medicos;
    }
}