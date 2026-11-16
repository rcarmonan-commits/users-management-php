# CRUD de Usuarios y Control de Gastos (#6) — Arquitectura Hexagonal, DDD y CQRS en PHP 8.3

Repositorio desarrollado a partir del **Fork oficial** de [`arrietajohn/hexagonal-ddd-example-php`](https://github.com/arrietajohn/hexagonal-ddd-example-php) (`arrietajohn/users-management-php`), implementando el flujo completo **CRUDL** tanto para la entidad común **Usuario** como para la entidad asignada **Ejercicio #6: Gasto**.

## Datos Académicos

- **Universidad:** Universidad de Cartagena
- **Programa:** Ingeniería de Sistemas
- **Asignatura:** Desarrollo Web — Unidad 2
- **Actividad:** Guía de Desarrollo: CRUD de Usuarios con Arquitectura Hexagonal, DDD y CQRS en PHP
- **Estudiante:** Rosary Eilyn Carmona Navarro (`rcarmonan@unicartagena.edu.co`)
- **Ejercicio Asignado:** **N.º 6 — Entidad `Gasto`** (`fecha`, `valorTotalSinIVA`, `ivaTotal`, `valorTotalConIVA`, `nombreUsuario`, `lugar`, `descripcion`)

---

## 1. Arquitectura Construida desde el Dominio hacia Afuera

El sistema respeta de forma estricta la **Regla de Inversión de Dependencias (DIP)**:

```
Entrypoints (Web PRG + REST API + Swagger) ──► Application (UseCases / Commands / Queries / Ports) ──► Domain (Models / VOs / Enums / Events / Exceptions)
                                                         ▲
Infrastructure Adapters (MySQL / InMemory / Cache / Queue / REST / PHPMailer) ┘
```

- **`Domain/`**: Cero dependencias hacia capas externas. Contiene `UserModel`, `EmailDestinationModel` y `GastoModel`, junto con Value Objects inmutables (`GastoId`, `GastoFecha`, `GastoMonto` con aritmética exacta en centavos, `GastoLugar`, `GastoDescripcion`, `UserId`, `UserName`, `UserEmail`, `UserPassword`, `RoleId`), Enums (`UserRoleEnum`, `UserStatusEnum`, `GastoIvaRateEnum`), Excepciones de negocio (`InvalidGastoIvaConsistencyException`, etc.) y Eventos de Dominio (`EventDomain.php`, `User*DomainEvent`, `Gasto*DomainEvent`).
- **`Application/`**: Contratos de entrada (`Ports/In/*UseCase.php`), contratos de salida (`Ports/Out/*Port.php`, incluyendo `CachePort`, `DomainEventPublisherPort`, `TaxIndicatorRestPort` y `EmailSenderPort`), objetos **CQRS** (`Commands`, `Queries`, `Responses`), `Mappers` y Servicios orquestadores (`*Service.php`).
- **`Infrastructure/Adapters/`**:
  - **Base de datos MySQL (PDO):** `UserRepositoryMySQL` y `GastoRepositoryMySQL` con patrón *Repository + Data Mapper + Persistence DTO + Entity*, más scripts `database.sql` (local) y `cloud_migration.sql` (nube).
  - **Persistencia en Memoria con respaldo JSON (`InMemory`):** `InMemoryUserRepository` e `InMemoryGastoRepository` para pruebas aisladas y ejecución sin motor externo cuando `DB_DRIVER=auto` o `memory`.
  - **Caché:** `FileCacheAdapter` (`CachePort`) con invalidación automática por prefijo al crear, editar o eliminar gastos.
  - **Colas de Mensajería:** `FileQueueEventPublisherAdapter` (`DomainEventPublisherPort`) en formato JSONL (`storage/queue/domain_events.jsonl`).
  - **Cliente REST Externo:** `TaxIndicatorRestAdapter` (`TaxIndicatorRestPort`) con degradación segura a tarifas IVA/UVT vigentes.
  - **Correo SMTP:** `EmailSenderService` (`PHPMailer`) con bandeja de salida local segura (`storage/logs/emails/`) cuando SMTP no está configurado.
- **`Infrastructure/Entrypoints/`**:
  - **Web MVC/PRG (`?route=`):** `UserController`, `GastoController`, `View::render`, `Flash`, vistas HTML5 responsivas (`Views/users/*`, `Views/gastos/*`, `Views/auth/*`) y asistente JS de cálculo de IVA.
  - **RESTful JSON + OpenAPI Swagger:** `UserRestController`, `GastoRestController`, `ErrorHandler` centralizado (`401`, `404`, `409`, `422`, `500`), especificación `openapi.json` y visor interactivo Swagger UI en `?route=swagger` (`/swagger`).

---

## 2. Errores Detectados y Corregidos sobre el Proyecto Base (`d3d2c95`)

Durante la fase *"Compilar, ejecutar y corregir errores presentados durante la ejecución"* se diagnosticaron y resolvieron 5 problemas reales:

1. **Esquema SQL incompatible con el dominio (`database.sql`):** El script guía definía `id INT AUTO_INCREMENT` (mientras el Front Controller genera `UUIDv4` de 36 caracteres), omitía la columna `status` exigida por `UserRepositoryMySQL`, contenía comillas tipográficas `‘MEMBER’`, referenciaba `FOREIGN KEY (role_id)` sin declarar la columna y almacenaba clave plana de 7 caracteres (`admin123`). Se corrigió en `Infrastructure/Adapters/Persistence/MySQL/Database/mysql/database.sql` con `VARCHAR(64)`, `status`, `role_id` opcional y semilla `admin@crud.com` / `Admin12345` con hash BCrypt activo.
2. **Edición de usuario con contraseña en blanco:** El formulario `users/edit.php` indica dejar la clave en blanco para conservarla, pero `UserApplicationMapper::fromUpdateCommandToModel` invocaba siempre `UserPassword::fromPlainText('')` lanzando excepción. Se añadió soporte de `$fallbackPassword` para preservar el hash actual.
3. **Bloqueo post-inserción por credenciales SMTP de plantilla:** Con los valores por defecto de `.env.example`, `PHPMailer` lanzaba `EmailSenderException` después de insertar el usuario en MySQL. Ahora `EmailSenderService` detecta credenciales placeholder y deposita el correo renderizado en `storage/logs/emails/`.
4. **Bucle de redirección del guardia IIFE en Windows:** `dirname('/index.php')` en Windows retorna `\`, provocando redirección infinita en el guardia de `public/index.php`. Se normalizó con `str_replace('\\', '/', ...)`.
5. **Acoplamiento hacia `Infrastructure` en `EmailNotificationService`:** El servicio de aplicación resolvía internamente rutas físicas de `Infrastructure/Entrypoints/...`. Se desacopló inyectando el directorio de plantillas desde `DependencyInjection` (raíz de composición), preservando inversión de dependencias pura.

---

## 3. Ejecución Local, Docker y Pruebas

### Opción A: Ejecución inmediata con PHP 8.3 (Local)
```bash
cp .env.example .env
php -S localhost:8080 -t public public/index.php
```
- **Aplicación Web:** http://localhost:8080/?route=auth.login (Credenciales semilla: `admin@crud.com` / `Admin12345`)
- **Explorador Swagger UI:** http://localhost:8080/?route=swagger
- **Health Check REST:** http://localhost:8080/api/health

### Opción B: Ejecución completa con Docker Compose (PHP 8.3 + Apache + MySQL 8.0)
```bash
docker compose up --build -d
```
- Disponible en http://localhost:8080 con MySQL inicializado automáticamente desde `database.sql`.

### Suite Automatizada de Pruebas (45+ verificaciones)
```bash
php tests/run_tests.php
```

### Colección de Postman
Importar el archivo `postman/CRUD_Usuarios_Gastos_Hexagonal.postman_collection.json` en Postman y ejecutar el *Collection Runner* apuntando `baseUrl` a `http://localhost:8080`.

### Integración y Despliegue Continuo (GitHub Actions & Cloud)
- **Cloud Computing:** Incluye `render.yaml` y `Dockerfile` con puerto dinámico (`$PORT`) listos para **Render**, **Zeabur**, **Railway** o **Hugging Face Spaces**, más migración cloud en `Infrastructure/Adapters/Persistence/MySQL/Database/mysql/cloud_migration.sql`.
- **GitHub Actions:** El pipeline completo de lint, migración MySQL 8, pruebas y despliegue continuo se encuentra en `devops/github-actions-ci-cd.yml` (para activarlo en GitHub basta copiarlo a `.github/workflows/ci-cd.yml` desde la interfaz web o con un token con permiso `workflow`).