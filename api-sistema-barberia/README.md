# README Técnico — API Sistema Barbería

Documentación de referencia para probar la API en Bruno/Postman. Cubre autenticación, usuarios, servicios y citas (lo implementado hasta la Actividad 4, puntos 1–11).

---

## 1. Arranque rápido

```bash
composer install
cp .env.example .env
php artisan key:generate
# edita .env con tu MySQL: DB_DATABASE, DB_USERNAME, DB_PASSWORD
php artisan migrate:fresh --seed
php artisan serve
```

Base URL local: `http://127.0.0.1:8000/api`

Headers recomendados en **todas** las peticiones:
```
Accept: application/json
Content-Type: application/json
```

En las rutas protegidas, además:
```
Authorization: Bearer <token>
```

---

## 2. Usuarios de prueba (generados por el seeder)

| Rol | Email | Password | Notas |
|---|---|---|---|
| Admin (cuenta de evaluación) | `developer@barberia.com` | `Developer123!` | Fija para el profesor, no modificarla |
| Admin | `admin@barberia.com` | `Admin123!` | |
| Cliente | `user@barberia.com` | `Client123!` | Tiene citas y pedidos de ejemplo (seeder) |
| Barbero | `barbero1@barberia.com` | `Barbero123!` | Asignado a la sucursal 1 (Barbería Centro) |
| Barbero | `barbero2@barberia.com` | `Barbero123!` | Asignado a la sucursal 2 (Barbería Reforma) |

Hay además ~10 clientes y algunos barberos extra generados con Faker (contraseña `password` para todos los usuarios de Faker) — útiles para probar paginación y filtros con volumen.

---

## 3. Autenticación

### `POST /api/register` — público
Crea un cliente nuevo.

```json
{
  "name": "Ana Torres",
  "email": "ana@example.com",
  "phone": "9511234567",
  "birthdate": "1998-05-20",
  "password": "Segura123!",
  "password_confirmation": "Segura123!"
}
```
`201` con `user` y `token`. `422` si el email ya existe o el password no cumple la política (8+ caracteres, mayúscula, minúscula, número, símbolo).

### `POST /api/login` — público
```json
{ "email": "user@barberia.com", "password": "Client123!" }
```
`200` con `user` y `token`. `422` si las credenciales son incorrectas.

### `POST /api/forgot-password` — público
```json
{ "email": "user@barberia.com" }
```
`200`. El correo queda en `storage/logs/laravel.log` (`MAIL_MAILER=log`), no se envía uno real todavía.

### `POST /api/reset-password` — público
```json
{
  "token": "<token del log>",
  "email": "user@barberia.com",
  "password": "NuevaSegura123!",
  "password_confirmation": "NuevaSegura123!"
}
```

### `GET /api/user` — requiere token
Regresa el usuario autenticado.

### `POST /api/logout` — requiere token
Revoca el token con el que se hizo la petición.

---

## 4. Usuarios

### `GET /api/users` — requiere token, **solo admin**
Query params opcionales: `role` (`admin`|`client`|`barber`), `per_page`.
```
GET /api/users?role=barber&per_page=10
```
`403` si el token es de un cliente o barbero.

### `GET /api/users/{id}` — requiere token, admin o el propio usuario
`403` si intentas ver el perfil de alguien más sin ser admin. La respuesta nunca incluye `password`.

---

## 5. Servicios (catálogo)

### `GET /api/services` — público
Query params opcionales: `category`, `per_page`, `page`.
```
GET /api/services?category=Corte&per_page=10
```

### `GET /api/services/{id}` — público
`404` si el id no existe.

### `POST /api/services` — requiere token, **solo admin**
```json
{
  "name": "Corte + barba",
  "category": "Degradado",
  "price": 220,
  "duration": 60,
  "description": "Paquete completo",
  "image": "https://ejemplo.com/img.jpg"
}
```
`201`. `422` si falta `name`, `price` no es numérico, etc. `403` si el token no es de admin.

### `PUT /api/services/{id}` — requiere token, **solo admin**
Mismos campos que store, todos opcionales.

### `DELETE /api/services/{id}` — requiere token, **solo admin**

---

## 6. Citas

Reglas de visibilidad (aplicadas automáticamente por el controlador según el rol del token):
- **Cliente:** solo ve/edita sus propias citas.
- **Barbero:** solo ve/edita las citas asignadas a él.
- **Admin:** ve y edita todas.

### `GET /api/appointments` — requiere token
Query params opcionales: `status`, `branch_id`, `date_from`, `date_to`, `per_page`.
```
GET /api/appointments?status=pendiente&date_from=2026-07-01&date_to=2026-07-31&per_page=10
```

### `POST /api/appointments` — requiere token (rol cliente)
```json
{
  "service_id": 1,
  "branch_id": 1,
  "barber_id": 6,
  "date_time": "2026-08-15 11:00:00",
  "pay_online": true,
  "notify_whatsapp": true
}
```
`201`, estado inicial `pendiente`. `422` si `date_time` no es futura o algún id no existe.

### `GET /api/appointments/{id}` — requiere token, dueño/barbero asignado/admin
Incluye `history` con la bitácora de cambios de estado. `403` si la cita no es tuya.

### `PUT /api/appointments/{id}` — requiere token, dueño/barbero asignado/admin
Reprogramar fecha, servicio o barbero. Solo si la cita está `pendiente` o `confirmada`.
```json
{ "date_time": "2026-08-16 12:00:00" }
```
`422` si la cita ya está `completada` o `cancelada`.

### `PATCH /api/appointments/{id}/status` — requiere token
Único endpoint para cambiar el estado.
```json
{ "status": "confirmada", "note": "Confirmada por el barbero" }
```

**Máquina de estados:**

| Estado actual | Puede pasar a |
|---|---|
| `pendiente` | `confirmada`, `pospuesta`, `cancelada` |
| `confirmada` | `completada`, `pospuesta`, `cancelada` |
| `pospuesta` | `confirmada`, `cancelada` |
| `completada` | *(ninguno, estado final)* |
| `cancelada` | *(ninguno, estado final)* |

- Un **cliente** solo puede mover su cita a `cancelada` (`403` si intenta otra cosa).
- Cualquier transición fuera de la tabla (ej. `cancelada → confirmada`) responde `422`.

---

## 7. Casos de prueba sugeridos para Bruno

| # | Caso | Resultado esperado |
|---|---|---|
| 1 | `POST /api/login` con `user@barberia.com` / `Client123!` | `200` + token |
| 2 | `GET /api/user` sin header `Authorization` | `401` |
| 3 | `GET /api/user` con el token del paso 1 | `200`, datos del cliente |
| 4 | `POST /api/services` con token de cliente | `403` |
| 5 | `POST /api/services` con token de `admin@barberia.com` sin `name` | `422` con `errors.name` |
| 6 | `POST /api/services` con token de admin, body completo | `201` |
| 7 | `GET /api/services/9999` | `404` |
| 8 | `POST /api/register` con password `12345678` (sin mayúscula/símbolo) | `422` con `errors.password` |
| 9 | `POST /api/appointments` (token cliente) con una fecha pasada | `422` |
| 10 | `GET /api/appointments/{id}` de una cita de otro cliente | `403` |
| 11 | `PATCH /api/appointments/{id}/status` de `cancelada` a `confirmada` | `422` |
| 12 | `PATCH /api/appointments/{id}/status` a `cancelada` (token del cliente dueño) | `200` |

---

## 8. Formato de errores (idéntico en toda la API)

```jsonc
// 422 — validación
{ "message": "Los datos enviados no son válidos.", "errors": { "email": ["..."] } }

// 401 — sin token o token inválido
{ "message": "No autenticado. Envía un token válido en el header Authorization." }

// 403 — rol/dueño incorrecto
{ "message": "No tienes permisos para realizar esta acción." }

// 404 — recurso inexistente
{ "message": "El recurso solicitado no existe." }

// 500 — error interno (en debug incluye "exception" y "debug_message")
{ "message": "Ocurrió un error interno en el servidor." }
```

---

## 9. Pendiente (próximas actividades)

- CRUD de productos, sucursales, noticias y pedidos.
- Conectar el frontend React (hoy usa `mockApi.js`, no esta API).
- Colección Bruno versionada en `/bruno` del repositorio.
- Despliegue en VPS con Nginx + Certbot (HTTPS).
