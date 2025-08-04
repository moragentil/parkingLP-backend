# ParkingLP Backend

Backend para la gestión de zonas de estacionamiento, vehículos, usuarios y alarmas para la app ParkingLP.

## Tecnologías utilizadas

- **Framework:** Laravel 12.x
- **Lenguaje:** PHP 8.2+
- **Base de datos:** MySQL (configurable a SQLite/PostgreSQL)
- **ORM:** Eloquent
- **Autenticación:** Laravel Sanctum
- **Colas:** Laravel Queue (configurable)
- **Tareas programadas:** Laravel Scheduler
- **Logs:** Monolog (storage/logs/laravel.log)

## Estructura principal

- `app/` - Código fuente principal (Controllers, Models, Services)
- `routes/` - Rutas de la API (`api.php`)
- `database/` - Migraciones y seeders
- `config/` - Configuración de la app y servicios
- `public/` - Punto de entrada HTTP
- `.env` - Variables de entorno

## Instalación y configuración

1. **Clonar el repositorio**
   ```sh
   git clone https://github.com/moragentil/parkingLP-backend
   cd parkingLP-backend
   ```

2. **Instalar dependencias**
   ```sh
   composer install
   ```

3. **Configurar variables de entorno**
   - Copia `.env.example` a `.env` y ajusta según tu entorno.
   - Configura la conexión MySQL en `.env`:
     ```
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=parkinglp
     DB_USERNAME=tu_usuario
     DB_PASSWORD=tu_contraseña
     ```

4. **Generar clave de la aplicación**
   ```sh
   php artisan key:generate
   ```

5. **Migrar y seedear la base de datos**
   ```sh
   php artisan migrate --seed
   ```

6. **Levantar el servidor**
   ```sh
   php artisan serve
   ```

7. **Configurar tareas programadas (scheduler)**
    - Esto es para poder gestionar el corte de los estacionamientos en el horario de finalización de las zonas:
   - Para producción, agrega al cron del sistema:
     ```
     * * * * * cd /ruta/a/parkingLP-backend && php artisan schedule:run >> /dev/null 2>&1
     ```
   - En desarrollo, puedes ejecutar manualmente:
     ```
     php artisan schedule:run
     ```

8. **Seeder de usuario de prueba**
   ```sh
   php artisan db:seed --class=UsuarioSeeder
   ```
   Esto creará el usuario:
   - **Email:** prueba@parkinglp.com
   - **Contraseña:** password123

> Si necesitas otro usuario, puedes registrarlo vía la API `/api/register`.

## Endpoints principales

- **Autenticación:** `/api/login`, `/api/register`, `/api/logout`, `/api/me`
- **Usuarios:** `/api/usuarios`
- **Vehículos:** `/api/vehiculos`
- **Estacionamientos:** `/api/estacionamientos`
- **Zonas:** `/api/zonas`, `/api/zonas-leyenda`, `/api/zonas-mapa`
- **Alarmas:** `/api/alarmas`

> Todas las rutas protegidas requieren autenticación con token Bearer (Sanctum).

## Configuración de base de datos

- Por defecto, se usa MySQL. Puedes cambiar a SQLite/PostgreSQL editando `.env` y `config/database.php`.
- Crea la base de datos `parkinglp` en tu servidor MySQL antes de ejecutar las migraciones.

## Notas importantes

- El backend incluye lógica para finalizar automáticamente estacionamientos según el horario de la zona (ver Scheduler).
- Las alarmas se programan automáticamente para avisar antes del vencimiento del estacionamiento.
- El sistema soporta tarifas variables por zona y horario.

## Contacto y soporte

Para dudas o soporte, contacta a moragentil@gmail.com.

---

**Entrega:** Este repositorio contiene todo lo necesario para ejecutar el backend del proyecto ParkingLP, incluyendo migraciones, seeders, configuración y documentación.
