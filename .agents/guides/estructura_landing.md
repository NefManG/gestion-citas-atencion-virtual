# Estructura obligatoria — 07_landing

La Landing pública del Sistema de Gestión de Citas y Atención Virtual
del Hospital Boliviano Español deberá construirse de forma separada
del sistema administrativo ubicado en `06_codigo`.

## Estructura

```text
proyecto/07_landing/
├── app/
│   ├── Core/
│   │   └── Database.php
│   │
│   ├── Repositories/
│   │   ├── LandingEspecialidadRepository.php
│   │   └── LandingMedicoRepository.php
│   │
│   ├── Services/
│   │   └── LandingHospitalService.php
│   │
│   └── Views/
│       ├── layouts/
│       │   ├── header.php
│       │   └── footer.php
│       │
│       └── sections/
│           ├── hero.php
│           ├── especialidades.php
│           ├── medicos.php
│           ├── atencion.php
│           ├── beneficios.php
│           ├── llamada_accion.php
│           └── contacto.php
│
├── config/
│   ├── app.php
│   ├── database.php
│   └── landing.php
│
├── public/
│   ├── index.php
│   ├── .htaccess
│   │
│   └── assets/
│       ├── css/
│       │   ├── variables.css
│       │   ├── base.css
│       │   ├── layout.css
│       │   ├── components.css
│       │   ├── landing.css
│       │   └── responsive.css
│       │
│       ├── js/
│       │   └── landing.js
│       │
│       └── img/
│
├── docs/
│   ├── brief_landing.md
│   ├── fuentes_imagenes.md
│   ├── decisiones_visuales.md
│   └── validacion_final.md
│
├── tests/
│   └── results/
│
├── tools/
│   ├── validate_structure.php
│   └── validate_checkpoint.php
│
├── .env.example
└── README.md