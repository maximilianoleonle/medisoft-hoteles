# Política de almacenamiento y retención — Centro documental

**Fecha:** 2026-07-26 · **Estado:** vigente para venta · **Decide:** owner (Maximiliano)

Resuelve el pendiente 5 de `auditoria_comercial_modulos_20260725.md` §9 y el punto que quedó abierto en la migración `20260725_002` (abrir `documentos` a la venta con la política de PII pendiente).

---

## 1. Lo que se puede prometer por escrito

| Concepto | Límite | Dónde vive en el código |
|---|---|---|
| Espacio incluido por hotel | **2 GB** | `Documento::CUOTA_HOTEL_BYTES` |
| Peso máximo por archivo | **10 MB** | `Documento::MAX_UPLOAD_BYTES` |
| Formatos aceptados | PDF, JPG, JPEG, PNG, WEBP | `Documento::MIME_PERMITIDOS` |
| Aviso de espacio | Al llegar al **80 %** | `resumenAlmacenamiento()['cerca_del_limite']` |
| Bloqueo de subida | Al agotar los 2 GB | `validarCuotaHotel()` |

Un tipo de documento puede tener un tope propio más bajo (`documento_tipos.max_size_mb`); nunca uno más alto que los 10 MB.

**Qué consume cuota:** documentos **activos y archivados**. Archivar organiza, no libera disco.
**Qué no consume:** los dados de **baja**. Es la vía del hotelero para recuperar espacio sin llamar a soporte.

## 2. Qué pasa al borrar (leer antes de prometer)

Hoy la baja es **lógica**: la fila queda en estado `eliminado` y **el archivo permanece en el disco del servidor**. El documento deja de verse y deja de contar para la cuota, pero no ha sido destruido.

Consecuencia honesta para el contrato: **no se puede afirmar "sus documentos se eliminan definitivamente"** mientras no exista la purga física. Redacción segura: *"puede dar de baja un documento y dejará de estar disponible en el sistema"*.

**Deuda abierta (bloquea prometer borrado definitivo):** un proceso que elimine del disco los archivos en estado `eliminado` con más de N días, con registro de la purga. No existe hoy.

## 3. Retención propuesta

Sujeta a validación legal (punto 4 de "Antes de cobrar o firmar" en `estrategia_comercial/12_pendientes_imprescindibles.md`).

| Situación | Retención propuesta |
|---|---|
| Documento activo | Mientras el hotel sea cliente |
| Documento dado de baja | 30 días recuperable, luego purga física (cuando exista la purga) |
| Fin del contrato | 30 días para exportar; después, borrado a solicitud escrita |
| Respaldo | Incluido en el respaldo diario de la base (`tools/backup_db.sh`) |

## 4. Responsabilidades

El Centro documental guarda con frecuencia **identificaciones de huéspedes**: eso es dato personal sensible y define quién responde por él.

- **El hotel es el responsable** de los datos: decide qué sube, con qué base legal y a quién da acceso. Su aviso de privacidad debe contemplarlo.
- **Medisoft es encargado del tratamiento**: resguarda, controla accesos y no usa los documentos para otro fin.
- El acceso se controla por permisos: `documentos.view` para consultar y `documentos.all` para subir o vincular. Las descargas quedan registradas.
- Los archivos no son accesibles por URL pública directa.

**No prometer:** firma electrónica, validez legal automática, OCR, cifrado extremo a extremo, almacenamiento ilimitado ni borrado definitivo (§2).

## 5. Si un hotel necesita más espacio

No hay ampliación automatizada: la cuota es una constante del código, igual para todos. Ampliar a un hotel concreto exige cambio de código y liberar disco en el VPS. **No vender ampliaciones como si fueran un botón.** Antes de ofrecerlas hay que decidir si la cuota se vuelve configurable por hotel y a qué precio.

## 6. Qué falta antes de considerarlo cerrado

1. Purga física de los archivos dados de baja (§2) — bloquea prometer borrado definitivo.
2. Revisión legal del contrato y del aviso de privacidad.
3. Decidir si la cuota se vuelve configurable por hotel y su precio (§5).
4. Vigilar el disco del VPS: 2 GB × número de hoteles es el techo real que hay que dimensionar.
