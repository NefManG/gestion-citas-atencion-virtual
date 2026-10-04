#!/bin/bash
# ===========================================================
# Validación estática del V1__initial_schema.sql
# Paso 15 — Generación SQL
# ===========================================================

SCRIPT="V1__initial_schema.sql"
TABLAS=("paciente" "medico" "usuario" "especialidad" "medico_especialidad" "horario" "cita" "atencion_virtual" "registro_auditoria" "parametros_configuracion" "rol" "permiso" "usuario_rol" "rol_permiso" "cita_historico")

echo "=========================================="
echo "VALIDACIÓN ESTÁTICA - V1__initial_schema.sql"
echo "=========================================="
echo ""

# 1. Contar objetos creados
NUM_TABLAS=$(grep -c "CREATE TABLE" "$SCRIPT")
NUM_FUNCS=$(grep -c "CREATE OR REPLACE FUNCTION" "$SCRIPT")
NUM_TRIGGERS=$(grep -c "CREATE TRIGGER" "$SCRIPT")
NUM_ENUMS=$(grep -c "CREATE TYPE" "$SCRIPT")
NUM_ROLES=$(grep -c "CREATE ROLE" "$SCRIPT")
NUM_SEED=$(grep -c "INSERT INTO" "$SCRIPT")

echo "--- Objetos creados en el script ---"
echo "  TABLAS            : $NUM_TABLAS"
echo "  ENUMs/TIPOS       : $NUM_ENUMS"
echo "  FUNCIONES         : $NUM_FUNCS"
echo "  TRIGGERS          : $NUM_TRIGGERS"
echo "  ROLES             : $NUM_ROLES"
echo "  INSERTS semilla   : $NUM_SEED"
echo ""

# 2. Verificar todas las tablas necesarias existen
echo "--- Verificación de tablas requeridas ---"
TABLA_OK=0
TABLA_FAIL=0
for t in "${TABLAS[@]}"; do
  if grep -q "CREATE TABLE $t" "$SCRIPT"; then
    echo "  [OK] $t"
    TABLA_OK=$((TABLA_OK+1))
  else
    echo "  [FALLA] $t no existe"
    TABLA_FAIL=$((TABLA_FAIL+1))
  fi
done
echo "  Tablas: $TABLA_OK aprobadas, $TABLA_FAIL fallidas"
echo ""

# 3. Verificar índices y triggers sobre tablas existentes
echo "--- Índices/TRIGGERS sobre tablas existentes ---"
# Extraer solo ON <tabla>(  (índices y triggers, no funciones ni FK)
IDX_FAIL=0
IDX_OK=0
grep -E "ON [a-zA-Z_][a-zA-Z0-9_]* *\(" "$SCRIPT" | sed -E 's/.*ON ([a-zA-Z_][a-zA-Z0-9_]*).*/\1/' | while read tabla; do
  if [[ "$tabla" =~ ^fn_ ]] || [[ "$tabla" =~ ^h_existente$ ]]; then
    # Es una función (fn_...) o alias de tabla en join, saltar
    continue
  fi
  if grep -q "CREATE TABLE $tabla" "$SCRIPT"; then
    IDX_OK=$((IDX_OK+1))
  else
    echo "  [FALLA] Referencia a tabla inexistente: $tabla"
    IDX_FAIL=$((IDX_FAIL+1))
  fi
done
# Recalcular fuera del subshell
IDX_FAIL=$(grep -E "ON [a-zA-Z_][a-zA-Z0-9_]* *\(" "$SCRIPT" | sed -E 's/.*ON ([a-zA-Z_][a-zA-Z0-9_]*).*/\1/' | grep -v "^fn_\|^h_existente$" | while read tabla; do if ! grep -q "CREATE TABLE $tabla" "$SCRIPT"; then echo "1"; fi; done | wc -l)
IDX_OK=$(grep -E "ON [a-zA-Z_][a-zA-Z0-9_]* *\(" "$SCRIPT" | sed -E 's/.*ON ([a-zA-Z_][a-zA-Z0-9_]*).*/\1/' | grep -v "^fn_\|^h_existente$" | while read tabla; do if grep -q "CREATE TABLE $tabla" "$SCRIPT"; then echo "1"; fi; done | wc -l)
echo "  Índices/Triggers: $IDX_OK aprobados, $IDX_FAIL fallidos"
echo ""

# 4. Verificar INSERTs
echo "--- INSERTs con tablas existentes ---"
INSERT_FAIL=$(grep -E "INSERT INTO [a-zA-Z_]" "$SCRIPT" | sed -E 's/INSERT INTO ([a-zA-Z_][a-zA-Z0-9_]*).*/\1/' | while read t; do if ! grep -q "CREATE TABLE $t" "$SCRIPT"; then echo "1"; fi; done | wc -l)
if [ $INSERT_FAIL -gt 0 ]; then
  echo "  [FALLA] $INSERT_FAIL INSERTs referencian tablas inexistentes"
else
  echo "  [OK] Todos los INSERTs referencian tablas existentes"
fi
echo ""

# 5. Verificar triggers
echo "--- TRIGGERS definidos ---"
grep -E "CREATE TRIGGER" "$SCRIPT" | sed -E 's/CREATE TRIGGER ([a-zA-Z_][a-zA-Z0-9_]*).*/\1/'
echo ""

# 6. Verificar funciones
echo "--- FUNCIONES definidas ---"
grep -E "CREATE OR REPLACE FUNCTION" "$SCRIPT" | sed -E 's/CREATE OR REPLACE FUNCTION ([a-zA-Z_][a-zA-Z0-9_]*).*/\1/'
echo ""

# 7. Referencias a cita_historico
echo "--- Referencias a cita_historico ---"
REF_CH=$(grep -c "cita_historico" "$SCRIPT")
echo "  $REF_CH menciones a cita_historico encontradas"
echo ""

# 8. Verificar orden: tabla creada antes de sus índices
echo "--- Orden de creación (tabla antes de sus índices) ---"
ORDER_OK=0
ORDER_FAIL=0
for t in "${TABLAS[@]}"; do
  TABLE_LINE=$(grep -n "CREATE TABLE $t" "$SCRIPT" | head -1 | cut -d: -f1)
  if [ -n "$TABLE_LINE" ]; then
    IDX_LINES=$(grep -n "ON $t(" "$SCRIPT" | cut -d: -f1)
    if [ -n "$IDX_LINES" ]; then
      for idx_line in $IDX_LINES; do
        if [ $idx_line -gt $TABLE_LINE ]; then
          ORDER_OK=$((ORDER_OK+1))
        else
          echo "  [FALLA] Índice de $t en línea $idx_line ANTES de la tabla (línea $TABLE_LINE)"
          ORDER_FAIL=$((ORDER_FAIL+1))
        fi
      done
    fi
  fi
done
echo "  Índices con orden correcto: $ORDER_OK, incorrecto: $ORDER_FAIL"
echo ""

# 9. ENUMs existentes
echo "--- ENUMs creados ---"
grep "CREATE TYPE" "$SCRIPT" | sed -E 's/.*CREATE TYPE ([a-zA-Z_][a-zA-Z0-9_]*).*/\1/'
echo ""

# 10. Verificar FKs (REFERENCES) apuntan a tablas existentes
echo "--- FKs (REFERENCES) a tablas existentes ---"
FK_FAIL=0
grep -E "REFERENCES [a-zA-Z_]" "$SCRIPT" | sed -E 's/.*REFERENCES ([a-zA-Z_][a-zA-Z0-9_]*).*/\1/' | while read t; do
  if ! grep -q "CREATE TABLE $t" "$SCRIPT"; then
    echo "  [FALLA] FK a tabla inexistente: $t"
    FK_FAIL=$((FK_FAIL+1))
  fi
done
FK_FAIL=$(grep -E "REFERENCES [a-zA-Z_]" "$SCRIPT" | sed -E 's/.*REFERENCES ([a-zA-Z_][a-zA-Z0-9_]*).*/\1/' | while read t; do if ! grep -q "CREATE TABLE $t" "$SCRIPT"; then echo "1"; fi; done | wc -l)
if [ $FK_FAIL -gt 0 ]; then
  echo "  [FALLA] $FK_FAIL FKs referencian tablas inexistentes"
else
  echo "  [OK] Todas las FKs referencian tablas existentes"
fi
echo ""

echo "=========================================="
echo "RESULTADO"
echo "=========================================="
if [ $TABLA_FAIL -eq 0 ] && [ $IDX_FAIL -eq 0 ] && [ $INSERT_FAIL -eq 0 ] && [ $ORDER_FAIL -eq 0 ] && [ $FK_FAIL -eq 0 ]; then
  echo "  TODAS LAS VALIDACIONES APROBADAS"
  echo "  V1__initial_schema.sql está LISTO PARA DESPLIEGUE"
  exit 0
else
  echo "  HAY FALLAS EN LA VALIDACIÓN"
  echo "  V1__initial_schema.sql NO está listo para despliegue"
  exit 1
fi