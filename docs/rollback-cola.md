# Rollback - cola autonoma

## Fase 3B aplicada

### Codigo

Rollback seguro de codigo:

1. Revertir el commit de Fase 3B.
2. Verificar que desaparezcan rutas/vistas/modelo/controlador de CxP.
3. Ejecutar `php -l` sobre archivos afectados si el revert genera cambios.
4. Ejecutar health/preflight para confirmar que el sistema vuelve al estado anterior.

### Base de datos

La migracion ya aplicada creo tablas nuevas vacias:

- `cuentas_por_pagar`;
- `cuentas_por_pagar_movimientos`.

No borrar tablas ni datos sin autorizacion explicita.

Si se requiere rollback de DB:

1. Confirmar backup existente:
   - `src/storage/backups/phase3b_20260615_040742_before_cxp_medisoft_hoteles_import.sql`
   - SHA256: `24663D206AE15B86B001708D8BC2665541548443A0EA363548CAFC3FDF3A4D2C`
2. Solicitar autorizacion explicita antes de cualquier `DROP`, `DELETE` o restauracion.
3. Documentar conteos antes y despues.
4. No tocar Caja, cortes ni movimientos.

### Riesgo de datos

Bajo para datos operativos actuales porque:

- las tablas CxP estan vacias;
- no se generaron pagos;
- no se genero saldo automatico desde compras;
- no se integraron movimientos de Caja.

### No hacer sin autorizacion

- `DROP TABLE cuentas_por_pagar`;
- `DROP TABLE cuentas_por_pagar_movimientos`;
- borrar registros de `migrations`;
- restaurar dump completo encima de datos vivos;
- reset destructivo de Git;
- push.
