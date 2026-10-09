<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\LandingEspecialidadRepository;
use App\Repositories\LandingMedicoRepository;

final class LandingHospitalService
{
    private LandingEspecialidadRepository $especialidadRepository;

    private LandingMedicoRepository $medicoRepository;


    public function __construct()
    {
        $this->especialidadRepository =
            new LandingEspecialidadRepository();


        $this->medicoRepository =
            new LandingMedicoRepository();
    }


    public function getEspecialidades(): array
    {
        return
            $this->especialidadRepository
                ->getAllActive();
    }


    public function getMedicos(): array
    {
        return
            $this->medicoRepository
                ->getAllActive();
    }


    public function getLandingData(): array
    {
        return [

            'especialidades' =>
                $this->getEspecialidades(),

            'medicos' =>
                $this->getMedicos()
        ];
    }
}