# Workflow 07 — Landing pública Hospital Boliviano Español

## Regla general

Ejecutar UN checkpoint por interacción.

Cada checkpoint debe:

1. leer decisiones, guías, manifest y state;
2. ejecutar únicamente el alcance autorizado;
3. realizar las pruebas correspondientes;
4. guardar evidencia;
5. actualizar el state;
6. finalizar con `HUMAN_STATUS: PENDING`;
7. detenerse hasta recibir aprobación humana.

No avanzar automáticamente.

---

# CP-LAND-01 — Contrato y estructura

## Objetivo

Validar el contrato inicial de la Landing antes de programar.

Revisar:

- `decisiones_landing.md`;
- `estructura_landing.md`;
- `reglas_landing.md`;
- `politica_imagenes_landing.md`;
- estructura obligatoria de `07_landing`;
- tecnologías permitidas;
- restricciones;
- separación respecto a `06_codigo`.

## Condiciones

La Landing debe ser:

- pública;
- sin login;
- sin dashboard;
- sin CRUD administrativo;
- separada del sistema administrativo;
- orientada a información hospitalaria;
- read-only.

## Prueba

Ejecutar:

```bash
php proyecto/07_landing/tools/validate_checkpoint.php CP-LAND-01