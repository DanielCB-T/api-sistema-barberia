# Pruebas de API con Bruno — Sistema Barbería

Colección de [Bruno](https://www.usebruno.com/) versionable en el repo (archivos `.bru`).
Cubre toda la API separada por secciones, con **login que guarda el token**, casos felices
y **casos de error** (401 / 403 / 422 / 404).

## Estructura

```
bruno-barberia/
├── bruno.json                 # metadatos de la colección
├── collection.bru             # headers (Accept: application/json) y auth por defecto
├── environments/
│   └── Local.bru              # base_url, credenciales, ids y variables de token
├── Autenticacion/             # register, login admin/cliente, me, logout, cambiar/recuperar pass, errores
├── Usuarios/                  # listar/ver + error 403 sin rol admin
├── Productos/                 # CRUD + errores 401/422/404
├── Servicios/                 # CRUD + error 422
├── Sucursales/                # CRUD + error 422
├── Barberos/                  # crear/editar/eliminar + error 403
├── Noticias/                  # CRUD
├── Citas/                     # listar/crear/ver/reagendar/estado/reenviar-sms/eliminar + error 401
├── Carrito/                   # ver/agregar/actualizar/eliminar item
├── Ordenes/                   # listar/checkout/ver
└── Notificaciones/            # listar/marcar-una/marcar-todas/eliminar
```

## Cómo usarla (app de escritorio Bruno)

1. Abre Bruno → **Open Collection** → selecciona la carpeta `bruno-barberia`.
2. Arriba a la derecha, elige el entorno **Local**.
3. Ajusta `base_url` si hace falta. Por defecto: `http://localhost:8000/api`
   (levanta la API con `php artisan serve`). Para tu servidor:
   `http://177.7.32.156/api-sistema-barberia/api`.
4. Ejecuta **Autenticacion → Login Admin** y **Login Cliente**. Esos requests guardan
   automáticamente `token` y `client_token` en el entorno; el resto de las carpetas los usan.
5. Ya puedes correr cualquier carpeta o request. Cada uno trae asserts en la pestaña **Tests**.

## Credenciales (del seeder)

| Rol     | Email                  | Password      |
|---------|------------------------|---------------|
| Admin   | admin@barberia.com     | Admin123!     |
| Cliente | user@barberia.com      | Client123!    |

> Si no coinciden, corre `php artisan migrate:fresh --seed` o ajusta las variables
> `admin_email/admin_password/client_email/client_password` en `environments/Local.bru`.

## Ejecutar desde consola (CI / línea de comandos)

```bash
npm install -g @usebruno/cli
cd bruno-barberia
bru run --env Local                 # toda la colección
bru run Productos --env Local       # solo una sección
bru run --env Local --reporter-html reporte.html   # con reporte HTML
```
> Para que los tokens existan al correr por CLI, ejecuta primero la carpeta
> `Autenticacion` (o corre todo; Bruno respeta el orden por `seq`).

## Casos de error incluidos

- **401** sin token: `Autenticacion/ERROR acceso sin token`, `Productos/ERROR crear sin token`,
  `Citas/ERROR crear sin token`.
- **403** sin rol admin: `Usuarios/ERROR listar sin rol admin`, `Barberos/ERROR crear sin rol admin`.
- **422** validación: `Autenticacion/ERROR credenciales inválidas`,
  `Productos|Servicios|Sucursales/ERROR validación al crear`.
- **404** no encontrado: `Productos/ERROR producto inexistente`.

## Notas

- Las variables `*_id` (product_id, branch_id, barber_id, etc.) apuntan a registros del
  seeder. Si tu base tiene otros ids, ajústalos en `environments/Local.bru`.
- Los CRUD de crear→editar→eliminar encadenan el id mediante variables `*_id_created`
  (el "crear" guarda el id y el "editar/eliminar" lo reutilizan). Córrelos en orden.
- En **Citas → Crear**, `date_time` debe ser futuro y dentro del horario/disponibilidad de la
  sucursal; si da 422, ajusta `future_datetime`.
- Los requests que dependen de estado (citas, carrito, órdenes, notificaciones) aceptan varios
  códigos válidos (p. ej. 200/422) para no fallar por datos previos; revisa el detalle en cada test.
- **Logout** revoca el token: córrelo al final y vuelve a hacer Login si sigues probando.
