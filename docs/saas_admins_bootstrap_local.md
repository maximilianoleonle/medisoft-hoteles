# Bootstrap local de SaaS admins

El Panel Medisoft interno no usa `usuarios.rol` para autorizar acceso SaaS.
Los roles operativos de hotel (`gerente`, `administrador`, `recepcionista`)
siguen viviendo en `usuarios` y `hotel_usuarios`.

El acceso al Panel SaaS se controla con la tabla `saas_admins`:

- `owner`
- `admin`
- `soporte`

## Migracion requerida

Ejecutar primero la migracion:

```bash
migrations/20260526_010_create_saas_admins.sql
```

No inserta administradores automaticamente.

## Promover un usuario existente en local

Modo seguro, solo muestra lo que haria:

```bash
docker exec medisoft_hoteles_app php /var/www/html/tools/saas/promover_saas_admin.php --usuario=admin --rol=owner
```

Modo escritura explicito:

```bash
docker exec medisoft_hoteles_app php /var/www/html/tools/saas/promover_saas_admin.php --usuario=admin --rol=owner --execute --confirm=admin
```

La herramienta solo corre por CLI y solo con `APP_ENV=local`.

## Validacion esperada

- Un usuario autenticado que no exista en `saas_admins` no debe entrar a `/admin/saas/hoteles`.
- Un usuario autenticado con fila activa en `saas_admins` debe entrar a `/admin/saas/hoteles`.
- Un usuario hotelero creado desde el Panel SaaS no recibe acceso SaaS salvo que tambien exista en `saas_admins`.
