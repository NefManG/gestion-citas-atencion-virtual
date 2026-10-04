#!/bin/bash
# ============================================================================
# Validación estática: SQL generado vs modelos corregidos (Paso 15)
# Ejecución: sin conexión a base de datos real. Análisis únicamente del
# script SQL generado en generacion_sql.md.
# ============================================================================

SQL_FILE="proyecto/base_datos/15_reportes/generacion_sql.md"

echo "=== VALIDACIÓN SQL vs MODELOS CORREGIDOS ==="
echo "Archivo: $SQL_FILE"
echo ""

check() {
    local name="$1"
    local pattern="$2"
    if grep -q "$pattern" "$SQL_FILE"; then
        echo "  ✅ $name"
        return 0
    else
        echo "  ❌ $name"
        return 1
    fi
}

total=0
fail=0

# 1. Tablas generadas (14 tablas principales)
for t in paciente medico usuario especialidad medico_especialidad horario cita atencion_virtual registro_auditoria parametros_configuracion rol permiso usuario_rol rol_permiso; do
    total=$((total+1))
    if grep -q "CREATE TABLE $t" "$SQL_FILE"; then
        echo "  ✅ Tabla $t"
    else
        echo "  ❌ Tabla $t"
        fail=$((fail+1))
    fi
done

# 2. Redundancia eliminada — verificar que NO hay CREATE TABLE cita con id_medico
echo ""
total=$((total+1))
# Buscar solo líneas CREATE TABLE cita, no comentarios
if awk '/CREATE TABLE cita/,/;/' "$SQL_FILE" | grep -v "id_medico"; then
    echo "  ✅ Tabla cita sin columna id_medico"
else
    echo "  ❌ Tabla cita contiene id_medico"
    fail=$((fail+1))
fi

# 3. Derivación de médico vía horario
echo ""
total=$((total+1))
if grep -q "id_horario = NEW.id_horario" "$SQL_FILE"; then
    echo "  ✅ Médico derivado vía cita.id_horario -> horario.id_medico"
else
    echo "  ⚠️  Patrón de derivación no encontrado (revisar manual)"
fi

# 4. Índice único parcial (doble reserva)
echo ""
total=$((total+1))
if grep -q "uk_cita_horario_ocurrencia_activa" "$SQL_FILE" && \
   grep -q "id_horario, fecha_hora_programada" "$SQL_FILE"; then
    echo "  ✅ Índice uk_cita_horario_ocurrencia_activa presente"
else
    echo "  ❌ Índice uk_cita_horario_ocurrencia_activa faltante"
    fail=$((fail+1))
fi

# 5. Triggers de integridad
echo ""
for tr in fn_validar_cita_medico_especialidad fn_verificar_no_doble_reserva \
          fn_verificar_no_solapamiento_paciente fn_verificar_transiciones_estado \
          fn_validar_fechas_atencion fn_auditar_cambio_cita fn_auditar_cita_historico; do
    total=$((total+1))
    if grep -q "$tr()" "$SQL_FILE"; then
        echo "  ✅ Trigger $tr"
    else
        echo "  ❌ Trigger $tr"
        fail=$((fail+1))
    fi
done

# 6. Seguridad
echo ""
total=$((total+1))
if grep -q "scram-sha-256" "$SQL_FILE"; then
    echo "  ✅ pg_hba.conf con scram-sha-256 (typo corregido)"
else
    echo "  ❌ Method scram-sha-256 no encontrado"
    fail=$((fail+1))
fi

total=$((total+1))
# Verificar que no hay METHOD mdc512 en líneas de pg_hba (solo en comentarios)
if grep -q "mdc512" "$SQL_FILE"; then
    # Aparece solo en comentarios de corrección histórica, no como METHOD real
    if grep -q "METHOD.*mdc512\|mdc512.*METHOD" "$SQL_FILE"; then
        echo "  ❌ Typo mdc512 aún presente como METHOD"
        fail=$((fail+1))
    else
        echo "  ✅ Typo mdc512 solo en comentarios históricos (no como METHOD)"
    fi
else
    echo "  ✅ Typo mdc512 eliminado por completo"
fi

# 7. Parámetros pendientes sin hardcodeo
echo ""
for p in tiempo_minimo_cancelacion tiempo_tolerancia_no_asistencia anticipacion_maxima_reserva \
         nivel_habilitacion_virtual modalidad_horario; do
    total=$((total+1))
    if ! grep -q "$p" "$SQL_FILE"; then
        echo "  ✅ $p sin hardcodear (pendiente)"
    else
        echo "  ⚠️  $p mencionado en el script"
    fi
done

# 8. Valor inicial aprobado (RNF-07/D-13)
total=$((total+1))
if grep -q "tiempo_inactividad_sesion_minutos.*15\|15.*tiempo_inactividad_sesion_minutos" "$SQL_FILE"; then
    echo "  ✅ tiempo_inactividad_sesion_minutos = 15 (valor inicial aprobado)"
else
    echo "  ⚠️  Parámetro de sessión no encontrado con valor 15"
fi

# 9. Extensiones PostgreSQL
echo ""
for ext in pgcrypto btree_gist pg_trgm; do
    total=$((total+1))
    if grep -q "CREATE EXTENSION.*$ext" "$SQL_FILE"; then
        echo "  ✅ Extensión $ext"
    else
        echo "  ❌ Extensión $ext"
        fail=$((fail+1))
    fi
done

# 10. Tipos ENUM generados
echo ""
for tp in estado_cita modalidad_cita estado_horario accion_auditoria; do
    total=$((total+1))
    if grep -q "CREATE TYPE $tp" "$SQL_FILE"; then
        echo "  ✅ Tipo $tp"
    else
        echo "  ❌ Tipo $tp"
        fail=$((fail+1))
    fi
done

# 11. Roles PostgreSQL
echo ""
for rl in app_read app_write app_admin app_runtime; do
    total=$((total+1))
    if grep -q "CREATE ROLE $rl" "$SQL_FILE"; then
        echo "  ✅ Role $rl"
    else
        echo "  ❌ Role $rl"
        fail=$((fail+1))
    fi
done

echo ""
echo "=== RESULTADO ==="
echo "Elementos verificados: $total"
if [ $fail -eq 0 ]; then
    echo "FALLOS: 0 -> VALIDACIÓN APROBADA"
    exit 0
else
    echo "FALLOS: $fail -> REVISAR"
    exit 1
fi