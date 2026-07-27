# API Sistema Barbería — Actividad 4

API REST en Laravel con autenticación por token (Sanctum) para el sistema de gestión de citas de una barbería.

## Integrantes

- Nombre completo:
- Nombre completo:

## Tecnologías

- Laravel 12 / PHP 8.2
- MySQL
- Laravel Sanctum (autenticación por token)
- Bruno (pruebas de API)

## Requisitos previos

- PHP 8.2+, Composer
- MySQL (no MariaDB)

## Instalación local

```bash
git clone <url-de-este-repo>
cd api-sistema-barberia
composer install
cp .env.example .env
php artisan key:generate
```

Edita `.env` y define tu base de datos:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=api_sistema_barberia
DB_USERNAME=root
DB_PASSWORD=tu_password
```

Crea la base de datos vacía en MySQL (`CREATE DATABASE api_sistema_barberia;`) y luego:

```bash
php artisan migrate:fresh --seed
php artisan serve
```

La API queda disponible en `http://127.0.0.1:8000/api`.

## Usuarios de prueba (creados por el seeder)

| Rol | Email | Password | Notas |
|---|---|---|---|
| Admin (evaluación) | developer@barberia.com | Developer123! | Cuenta fija para el profesor, no la modifiquen |
| Admin | admin@barberia.com | Admin123! | |
| Cliente | user@barberia.com | Client123! | |
| Barbero | barbero1@barberia.com | Barbero123! | Asignado a la sucursal 1 |
| Barbero | barbero2@barberia.com | Barbero123! | Asignado a la sucursal 2 |

## Endpoints disponibles (Actividad 4)

| Método | Endpoint | Protegido | Descripción |
|---|---|---|---|
| POST | `/api/register` | No | Registra un cliente nuevo, regresa token |
| POST | `/api/login` | No | Login, regresa token |
| POST | `/api/forgot-password` | No | Envía correo con enlace de recuperación (en local queda en `storage/logs/laravel.log` porque `MAIL_MAILER=log`) |
| POST | `/api/reset-password` | No | Aplica la nueva contraseña con el token recibido |
| GET | `/api/user` | Sí (Bearer token) | Regresa el usuario autenticado |
| POST | `/api/logout` | Sí (Bearer token) | Revoca el token actual |

Para las rutas protegidas, se envía el header:

```
Authorization: Bearer <token_que_regresó_login_o_register>
```

Si no se envía o el token no es válido, la API responde `401 Unauthorized`.

## Modelo de datos

El diagrama ER completo y la justificación de cada tabla están en el repositorio del análisis del proyecto (ver documento del sistema completo). Esta actividad crea todas las tablas del modelo (`users`, `branches`, `services`, `products`, `news`, `appointments`, `appointment_status_history`, `orders`, `order_items`, `payments`, `notifications`), aunque el CRUD expuesto por ahora es solo el de autenticación; el resto de endpoints se agrega en actividades posteriores.

## Pruebas con Bruno

Pendiente: exportar la colección de Bruno a la carpeta `/bruno` de este repo (login, ruta protegida con y sin token).

## Despliegue

Pendiente: desplegar en el VPS asignado, con Nginx como proxy reverso y HTTPS vía Certbot.
