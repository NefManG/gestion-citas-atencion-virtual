# Performance y hardening

## CP-LAND-15

Se realizó una revisión de rendimiento y seguridad básica
de la Landing pública.

### Performance

- CSS separado por responsabilidades.
- JavaScript externo.
- JavaScript cargado con defer.
- Sin frameworks frontend.
- Sin librerías de animación.
- Sin imágenes externas pesadas.
- Animaciones implementadas con CSS e IntersectionObserver.

### Seguridad y arquitectura

- public/index.php no contiene SQL.
- Las consultas están encapsuladas en repositories.
- Uso de PDO para PostgreSQL.
- Landing en modo READ_ONLY.
- Salida dinámica escapada mediante htmlspecialchars.
- No existen INSERT, UPDATE o DELETE desde la Landing.
- Errores internos de base de datos no se muestran al usuario.
- No existen credenciales visibles en HTML.

## Resultado

CP-LAND-15: PASSED