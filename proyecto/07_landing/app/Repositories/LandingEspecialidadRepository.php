<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class LandingEspecialidadRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo =
            Database::getInstance()->getPdo();
    }


    /**
     * Obtiene únicamente especialidades activas.
     *
     * La Landing es READ-ONLY.
     */
    public function getAllActive(): array
    {
        $sql = '
            SELECT
                id_especialidad AS id,
                nombre,
                descripcion
            FROM especialidad
            WHERE activo = true
            ORDER BY nombre ASC
        ';


        $statement =
            $this->pdo->prepare($sql);


        $statement->execute();


        $especialidades =
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );


        foreach ($especialidades as &$especialidad) {

            $especialidad['id'] =
                (int) $especialidad['id'];
        }

        unset($especialidad);


        return $especialidades;
    }
}