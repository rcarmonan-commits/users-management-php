<?php

declare(strict_types=1);

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA Y DE SEGURIDAD — FRONT CONTROLLER: public/index.php
 * ============================================================================
 * CAPA: Punto Único de Entrada HTTP (`public/index.php`)
 *
 * CICLO DE VIDA DE UNA PETICIÓN HTTP:
 *   1. Entrega de recursos estáticos (`?asset=css` / `?asset=js`) con MIME seguro.
 *   2. Arranque de `DependencyInjection::boot()` y carga de `SecurityGuard`.
 *   3. Despacho temprano de API REST (`/api/*`), `/openapi.json` y `/swagger`.
 *   4. Apertura de sesión endurecida (`HttpOnly`, `SameSite=Lax`, `use_strict_mode=1`).
 *   5. Resolución contra lista blanca `WebRoutes::routes()` (404 / 405).
 *   6. Compuerta Global Anti-CSRF: toda petición Web `POST` debe presentar un
 *      token criptográfico válido verificado con `hash_equals()` (`HTTP 403` si falla).
 *   7. Compuerta de Autenticación y RBAC (Control de Acceso Basado en Roles).
 *   8. Invocación del Caso de Uso de Aplicación y respuesta PRG (*Post-Redirect-Get*).
 * ============================================================================
 */

// 1. Soporte nativo para archivos físicos con php -S localhost:8080 -t public public/index.php
if (PHP_SAPI === 'cli-server') {
    $cliPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $candidate = __DIR__ . $cliPath;
    if ($cliPath !== '/' && $cliPath !== '/index.php' && is_file($candidate)) {
        return false;
    }
}

// 2. Entrega universal de CSS y JS con cabeceras nosniff
if (isset($_GET['asset'])) {
    $asset = (string) $_GET['asset'];
    header('X-Content-Type-Options: nosniff');
    if ($asset === 'css') {
        header('Content-Type: text/css; charset=UTF-8');
        readfile(__DIR__ . '/../Infrastructure/Entrypoints/Web/Presentation/css/styles.css');
        exit;
    }
    if ($asset === 'js') {
        header('Content-Type: application/javascript; charset=UTF-8');
        readfile(__DIR__ . '/../Infrastructure/Entrypoints/Web/Presentation/js/validaciones.js');
        exit;
    }
}

require_once __DIR__ . '/../Common/ClassLoader.php';
require_once __DIR__ . '/../Common/DependencyInjection.php';
require_once __DIR__ . '/../Common/SecurityGuard.php';
require_once __DIR__ . '/../Infrastructure/Entrypoints/Web/Presentation/View.php';
require_once __DIR__ . '/../Infrastructure/Entrypoints/Web/Presentation/Flash.php';

DependencyInjection::boot();

// ──────────────────────────────────────────────────────────────
// 3. Resolución de ruta entrante para API REST y Swagger OpenAPI
// ──────────────────────────────────────────────────────────────
$rawUriPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$normalizedScriptDir = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'))), '/');
$relativePath = $rawUriPath;

if ($normalizedScriptDir !== '' && str_starts_with($relativePath, $normalizedScriptDir)) {
    $relativePath = substr($relativePath, strlen($normalizedScriptDir));
}
$relativePath = '/' . ltrim($relativePath, '/');

if (isset($_GET['api'])) {
    $relativePath = '/api/' . ltrim((string) $_GET['api'], '/');
}

if ($relativePath === '/openapi.json') {
    serveOpenApiSpec();
}

if ($relativePath === '/swagger') {
    serveSwaggerUi();
}

if (str_starts_with($relativePath, '/api/')) {
    dispatchRestApi($relativePath);
}

// ──────────────────────────────────────────────────────────────
// 4. Inicio de sesión endurecida + Guardia IIFE de ruta base
// ──────────────────────────────────────────────────────────────
SecurityGuard::startHardenedSession();
SecurityGuard::applyHttpSecurityHeaders();

(function () use ($rawUriPath, $normalizedScriptDir): void {
    $requestPath = rtrim(str_replace('\\', '/', $rawUriPath), '/');
    if ($normalizedScriptDir !== '' && $requestPath !== $normalizedScriptDir && !str_starts_with($requestPath, $normalizedScriptDir . '/')) {
        $dest = isset($_SESSION['auth']['id']) ? 'home' : 'auth.login';
        $safeLocation = SecurityGuard::sanitizeHeaderValue($normalizedScriptDir . '/index.php?route=' . $dest);
        header('Location: ' . $safeLocation);
        exit;
    }
})();

Flash::start();

// ──────────────────────────────────────────────────────────────
// Helpers de Autenticación y Autorización RBAC
// ──────────────────────────────────────────────────────────────
function isLoggedIn(): bool
{
    return isset($_SESSION['auth']['id']);
}

/**
 * @return array<string, string>
 */
function getLoggedUser(): array
{
    return is_array($_SESSION['auth'] ?? null) ? $_SESSION['auth'] : array();
}

function isAdminUser(): bool
{
    $logged = getLoggedUser();

    return ($logged['role'] ?? '') === UserRoleEnum::ADMIN;
}

// ──────────────────────────────────────────────────────────────
// 5. Enrutamiento Web (?route=) contra Lista Blanca
// ──────────────────────────────────────────────────────────────
$route = isset($_GET['route']) ? trim((string) $_GET['route']) : 'home';
$routes = WebRoutes::routes();

if (!isset($routes[$route])) {
    http_response_code(404);
    View::render('home', buildHomeViewData('Ruta no encontrada.'));
    exit;
}

$definition = $routes[$route];
$httpMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($httpMethod !== $definition['method']) {
    http_response_code(405);
    View::render('home', buildHomeViewData('Método HTTP no permitido para esta operación.'));
    exit;
}

// ──────────────────────────────────────────────────────────────
// 6. Compuerta Global Anti-CSRF (OWASP A01) para peticiones POST
// ──────────────────────────────────────────────────────────────
if ($httpMethod === 'POST') {
    $submittedCsrf = isset($_POST['_csrf_token'])
        ? (string) $_POST['_csrf_token']
        : (isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? (string) $_SERVER['HTTP_X_CSRF_TOKEN'] : null);

    if (!SecurityGuard::verifyCsrfToken($submittedCsrf)) {
        http_response_code(403);
        View::render('home', buildHomeViewData('Acción bloqueada por protección anti-CSRF: token inválido o sesión expirada. Recarga el formulario e inténtalo de nuevo.'));
        exit;
    }
}

$publicActions = array(
    'home',
    'about',
    'login',
    'authenticate',
    'logout',
    'forgot',
    'forgot.send',
    'create',
    'store',
    'swagger',
    'openapi',
);

if (!in_array($definition['action'], $publicActions, true) && !isLoggedIn()) {
    Flash::setMessage('Debes iniciar sesión para acceder a esta sección.');
    View::redirect('auth.login');
}

// Guardia de Seguridad: Si el usuario inició sesión con clave temporal, forzar el cambio antes de permitir cualquier otra acción
if (isLoggedIn() && !empty($_SESSION['auth']['must_change_password'])) {
    $allowedWhenMustChange = array('change_password', 'change_password.save', 'logout');
    if (!in_array($definition['action'], $allowedWhenMustChange, true)) {
        Flash::setMessage('Has ingresado con una clave temporal. Por seguridad, debes definir tu nueva contraseña permanente para continuar.');
        View::redirect('auth.change-password');
    }
}

try {
    switch ($definition['action']) {
        case 'home':
            View::render('home', buildHomeViewData());
            break;

        case 'about':
            View::render('about', array(
                'pageTitle' => 'Guía de Arquitectura Hexagonal, DDD, CQRS y Seguridad OWASP',
            ));
            break;

                case 'api.tester':
            View::render('api/tester', array(
                'pageTitle' => 'Consola Interactiva de Pruebas de la API REST — Postman & Buenas Prácticas SOLID / CQRS',
            ));
            break;

case 'reports':
            View::render('reports/index', buildReportsViewData());
            break;

        case 'swagger':
            serveSwaggerUi();
            break;

        case 'openapi':
            serveOpenApiSpec();
            break;

        // ── CRUDL Entidad Común: Usuarios (con RBAC) ──
        case 'create':
            View::render('users/create', buildCreateUserViewData());
            break;

        case 'store':
            $controller = DependencyInjection::getUserController();
            $form = getCreateUserFormData();
            // Política RBAC (Anti-Privilege Escalation): solo ADMIN puede asignar otro rol distinto de MEMBER
            if (!isAdminUser()) {
                $form['role'] = UserRoleEnum::MEMBER;
            }
            $form['id'] = generateUuid4();
            $errors = validateCreateUserForm($form);

            if (!empty($errors)) {
                Flash::setOld(SecurityGuard::sanitizeOldInput($form));
                Flash::setErrors($errors);
                Flash::setMessage('Corrige los errores del formulario.');
                View::redirect('users.create');
            }

            $request = new CreateUserWebRequest(
                $form['id'],
                $form['name'],
                $form['email'],
                $form['password'],
                $form['role']
            );

            $controller->store($request);

            Flash::setSuccess('Usuario registrado correctamente.');
            View::redirect(isLoggedIn() ? 'users.index' : 'auth.login');
            break;

        case 'index':
            $controller = DependencyInjection::getUserController();
            $users = $controller->index();
            View::render('users/list', buildListUsersViewData($users));
            break;

        case 'show':
            $controller = DependencyInjection::getUserController();
            $id = isset($_GET['id']) ? trim((string) $_GET['id']) : '';
            $user = $controller->show($id);
            View::render('users/show', array(
                'pageTitle' => 'Detalle de usuario',
                'user' => $user,
                'message' => Flash::message(),
            ));
            break;

        case 'edit':
            $controller = DependencyInjection::getUserController();
            $id = isset($_GET['id']) ? trim((string) $_GET['id']) : '';
            $logged = getLoggedUser();
            if (!isAdminUser() && ($logged['id'] ?? '') !== $id) {
                Flash::setMessage('Solo un Administrador puede editar perfiles de terceros.');
                View::redirect('users.index');
            }
            $user = $controller->show($id);
            View::render('users/edit', buildEditUserViewData($user));
            break;

        case 'update':
            $controller = DependencyInjection::getUserController();
            $form = getUpdateUserFormData();
            $logged = getLoggedUser();

            if (!isAdminUser() && ($logged['id'] ?? '') !== $form['id']) {
                Flash::setMessage('Solo un Administrador puede modificar perfiles de terceros.');
                View::redirect('users.index');
            }

            // Si no es ADMIN, conservar rol y estado vigentes de la base de datos
            if (!isAdminUser()) {
                $existingTarget = $controller->show($form['id']);
                $form['role'] = $existingTarget->getRole();
                $form['status'] = $existingTarget->getStatus();
            }

            $errors = validateUpdateUserForm($form);

            if (!empty($errors)) {
                Flash::setOld(SecurityGuard::sanitizeOldInput($form));
                Flash::setErrors($errors);
                Flash::setMessage('Corrige los errores del formulario.');
                header('Location: ?route=users.edit&id=' . urlencode(SecurityGuard::sanitizeHeaderValue($form['id'])));
                exit;
            }

            $request = new UpdateUserWebRequest(
                $form['id'],
                $form['name'],
                $form['email'],
                $form['password'],
                $form['role'],
                $form['status']
            );

            $controller->update($request);

            // Si el usuario editó su propio perfil, sincronizar nombre y correo en sesión
            if (($logged['id'] ?? '') === $form['id']) {
                $_SESSION['auth']['name'] = $form['name'];
                $_SESSION['auth']['email'] = strtolower($form['email']);
                $_SESSION['auth']['role'] = $form['role'];
            }

            Flash::setSuccess('Usuario actualizado correctamente.');
            View::redirect('users.index');
            break;

        case 'delete':
            if (!isAdminUser()) {
                Flash::setMessage('Acción restringida: solo un Administrador puede eliminar usuarios.');
                View::redirect('users.index');
            }
            $id = isset($_POST['id']) ? trim((string) $_POST['id']) : '';
            $logged = getLoggedUser();
            if ($id !== '' && ($logged['id'] ?? '') === $id) {
                Flash::setMessage('Por seguridad no puedes eliminar tu propia cuenta con sesión activa.');
                View::redirect('users.index');
            }

            $controller = DependencyInjection::getUserController();
            $controller->delete($id);
            Flash::setSuccess('Usuario eliminado correctamente.');
            View::redirect('users.index');
            break;

        // ── CRUDL Ejercicio #6: Gastos ──
        case 'gastos.create':
            View::render('gastos/create', buildCreateGastoViewData());
            break;

        case 'gastos.store':
            $gastoController = DependencyInjection::getGastoController();
            $form = getGastoFormData();
            $form['id'] = generateUuid4();
            $errors = validateGastoForm($form);

            if (!empty($errors)) {
                Flash::setOld(SecurityGuard::sanitizeOldInput($form));
                Flash::setErrors($errors);
                Flash::setMessage('Corrige los errores del formulario de gasto.');
                View::redirect('gastos.create');
            }

            $request = new CreateGastoWebRequest(
                $form['id'],
                $form['fecha'],
                $form['valorTotalSinIVA'],
                $form['ivaTotal'],
                $form['valorTotalConIVA'],
                $form['nombreUsuario'],
                $form['lugar'],
                $form['descripcion']
            );

            $gastoController->store($request);

            Flash::setSuccess('Gasto registrado correctamente.');
            View::redirect('gastos.index');
            break;

        case 'gastos.index':
            $gastoController = DependencyInjection::getGastoController();
            $nombreUsuario = isset($_GET['nombreUsuario']) ? trim((string) $_GET['nombreUsuario']) : null;
            $fechaDesde = isset($_GET['fechaDesde']) ? trim((string) $_GET['fechaDesde']) : null;
            $fechaHasta = isset($_GET['fechaHasta']) ? trim((string) $_GET['fechaHasta']) : null;
            $gastos = $gastoController->index($nombreUsuario, $fechaDesde, $fechaHasta);
            View::render('gastos/list', buildListGastosViewData($gastos, array(
                'nombreUsuario' => (string) ($nombreUsuario ?? ''),
                'fechaDesde' => (string) ($fechaDesde ?? ''),
                'fechaHasta' => (string) ($fechaHasta ?? ''),
            )));
            break;

        case 'gastos.show':
            $gastoController = DependencyInjection::getGastoController();
            $id = isset($_GET['id']) ? trim((string) $_GET['id']) : '';
            $gasto = $gastoController->show($id);
            View::render('gastos/show', array(
                'pageTitle' => 'Detalle de gasto',
                'gasto' => $gasto,
                'message' => Flash::message(),
            ));
            break;

        case 'gastos.edit':
            $gastoController = DependencyInjection::getGastoController();
            $id = isset($_GET['id']) ? trim((string) $_GET['id']) : '';
            $gasto = $gastoController->show($id);
            View::render('gastos/edit', array(
                'pageTitle' => 'Editar gasto',
                'gasto' => $gasto,
                'message' => Flash::message(),
                'errors' => Flash::errors(),
                'old' => Flash::old(),
            ));
            break;

        case 'gastos.update':
            $gastoController = DependencyInjection::getGastoController();
            $form = getGastoFormData(true);
            $errors = validateGastoForm($form);

            if (!empty($errors)) {
                Flash::setOld(SecurityGuard::sanitizeOldInput($form));
                Flash::setErrors($errors);
                Flash::setMessage('Corrige los errores del formulario de gasto.');
                header('Location: ?route=gastos.edit&id=' . urlencode(SecurityGuard::sanitizeHeaderValue($form['id'])));
                exit;
            }

            $request = new UpdateGastoWebRequest(
                $form['id'],
                $form['fecha'],
                $form['valorTotalSinIVA'],
                $form['ivaTotal'],
                $form['valorTotalConIVA'],
                $form['nombreUsuario'],
                $form['lugar'],
                $form['descripcion']
            );

            $gastoController->update($request);

            Flash::setSuccess('Gasto actualizado correctamente.');
            View::redirect('gastos.index');
            break;

        case 'gastos.delete':
            $gastoController = DependencyInjection::getGastoController();
            $id = isset($_POST['id']) ? trim((string) $_POST['id']) : '';
            $gastoController->delete($id);
            Flash::setSuccess('Gasto eliminado correctamente.');
            View::redirect('gastos.index');
            break;

        // ── Autenticación, Recuperación y Correo Endurecidos ──
        case 'login':
            if (isLoggedIn()) {
                View::redirect('home');
            }
            View::render('auth/login', array(
                'pageTitle' => 'Iniciar sesión',
                'message' => Flash::message(),
                'success' => Flash::success(),
                'errors' => Flash::errors(),
                'old' => Flash::old(),
            ));
            break;

        case 'authenticate':
            $email = trim(strtolower((string) ($_POST['email'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');
            $authErrors = array();

            if ($email === '') {
                $authErrors['email'] = 'El correo es obligatorio.';
            }
            if ($password === '') {
                $authErrors['password'] = 'La contraseña es obligatoria.';
            }

            if (!empty($authErrors)) {
                Flash::setErrors($authErrors);
                Flash::setOld(array('email' => $email));
                View::redirect('auth.login');
            }

            $loginRateKey = SecurityGuard::clientIp() . '|' . $email;
            if (!SecurityGuard::checkRateLimit('web_login', $loginRateKey, 5, 300)) {
                Flash::setMessage('Demasiados intentos fallidos de inicio de sesión. Por seguridad espera 5 minutos.');
                Flash::setOld(array('email' => $email));
                View::redirect('auth.login');
            }

            try {
                $loginUseCase = DependencyInjection::getLoginUseCase();
                $command = new LoginCommand($email, $password);
                $user = $loginUseCase->execute($command);
            } catch (InvalidCredentialsException $authEx) {
                SecurityGuard::recordFailedAttempt('web_login', $loginRateKey, 300);
                SecurityGuard::mitigateTimingEnumeration($password);
                throw $authEx;
            }

            SecurityGuard::clearAttempts('web_login', $loginRateKey);
            SecurityGuard::regenerateSessionOnLogin();

            $_SESSION['auth'] = array(
                'id' => $user->id()->value(),
                'name' => $user->name()->value(),
                'email' => $user->email()->value(),
                'role' => $user->role(),
            );

            if (SecurityGuard::isTemporaryPasswordActive($user->id()->value())) {
                $_SESSION['auth']['must_change_password'] = true;
                Flash::setMessage('Has iniciado sesión con una clave temporal. Por seguridad, ingresa a continuación tu nueva contraseña definitiva.');
                View::redirect('auth.change-password');
            }

            Flash::setSuccess('Bienvenido/a, ' . $user->name()->value() . '.');
            View::redirect('home');
            break;

        case 'logout':
            SecurityGuard::destroySessionCompletely();
            header('Location: ?route=auth.login');
            exit;

        case 'forgot':
            View::render('auth/forgot-password', array(
                'pageTitle' => 'Recuperar contraseña',
                'message' => Flash::message(),
                'success' => Flash::success(),
                'errors' => Flash::errors(),
                'old' => Flash::old(),
            ));
            break;

        case 'forgot.send':
            $forgotEmail = trim(strtolower((string) ($_POST['email'] ?? '')));

            if ($forgotEmail === '' || !filter_var($forgotEmail, FILTER_VALIDATE_EMAIL)) {
                Flash::setErrors(array('email' => 'Introduce un correo electrónico válido.'));
                Flash::setOld(array('email' => $forgotEmail));
                View::redirect('auth.forgot');
            }

            $forgotRateKey = SecurityGuard::clientIp() . '|' . $forgotEmail;
            if (!SecurityGuard::checkRateLimit('web_forgot', $forgotRateKey, 3, 300)) {
                Flash::setMessage('Has superado el límite de solicitudes de recuperación. Intenta nuevamente en 5 minutos.');
                View::redirect('auth.forgot');
            }
            SecurityGuard::recordFailedAttempt('web_forgot', $forgotRateKey, 300);

            $repository = DependencyInjection::getUserRepository();
            $foundUser = $repository->getByEmail(new UserEmail($forgotEmail));
            $channelUsed = 'none';

            if ($foundUser !== null && $foundUser->status() === UserStatusEnum::ACTIVE) {
                $tempPassword = bin2hex(random_bytes(5));
                $newPassword = UserPassword::fromPlainText($tempPassword);
                $updatedUser = $foundUser->changePassword($newPassword);

                $channelUsed = sendPasswordRecoveryEmail(
                    $foundUser->email()->value(),
                    $foundUser->name()->value(),
                    $tempPassword
                );

                // Seguridad operacional: solo sobrescribe la contraseña del usuario en MySQL
                // cuando el correo salió efectivamente al exterior (`smtp` o `https_api`).
                // Si el firewall Cloud (Render Free) bloqueó TCP 587/465 y no hay pasarela HTTPS 443,
                // preserva intacta la contraseña actual del usuario para jamás bloquear su cuenta.
                if ($channelUsed === 'smtp' || $channelUsed === 'https_api') {
                    $repository->update($updatedUser);
                    SecurityGuard::flagTemporaryPassword($foundUser->id()->value(), $foundUser->email()->value());
                }
            } else {
                SecurityGuard::mitigateTimingEnumeration($forgotEmail);
            }

            if ($channelUsed === 'outbox') {
                Flash::setMessage(
                    'Atención Cloud Free: Render Free bloquea por firewall los puertos salientes SMTP 25/465/587 hacia Alwaysdata. Por seguridad NO se alteró tu contraseña actual (puedes seguir iniciando sesión con tu clave habitual). Para entrega directa a Gmail desde Render Free, activa en 45 segundos la Pasarela HTTPS 443 (Google Apps Script / Brevo / Resend) desde el menú Admin -> Config. SMTP, o ejecuta la app localmente con Docker/PHP donde el puerto 587 está abierto.'
                );
            } else {
                Flash::setSuccess(
                    'Si el correo está registrado y la cuenta está activa, recibirás un mensaje con tu contraseña temporal.'
                );
            }
            View::redirect('auth.forgot');
            break;

        case 'change_password':
            if (!isLoggedIn()) {
                View::redirect('auth.login');
            }
            View::render('auth/change-password', array(
                'pageTitle' => 'Definir Nueva Contraseña',
                'message' => Flash::message(),
                'errors' => Flash::errors(),
                'old' => Flash::old(),
            ));
            break;

        case 'change_password.save':
            if (!isLoggedIn()) {
                View::redirect('auth.login');
            }
            $logged = getLoggedUser();
            $newPassword = (string) ($_POST['new_password'] ?? '');
            $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
            $pwdErrors = array();

            if (strlen($newPassword) < 8) {
                $pwdErrors['new_password'] = 'La nueva contraseña debe tener al menos 8 caracteres.';
            }
            if ($newPassword !== $confirmPassword) {
                $pwdErrors['confirm_password'] = 'Las contraseñas no coinciden.';
            }

            if (!empty($pwdErrors)) {
                Flash::setErrors($pwdErrors);
                View::redirect('auth.change-password');
            }

            $repository = DependencyInjection::getUserRepository();
            $userModel = $repository->getById(new UserId($logged['id']));
            if ($userModel === null) {
                Flash::setMessage('No se encontró el registro del usuario.');
                View::redirect('auth.login');
            }

            $newPasswordVo = UserPassword::fromPlainText($newPassword);
            $updatedUser = $userModel->changePassword($newPasswordVo);
            $repository->update($updatedUser);

            // Sincronizar en caliente la tabla legada `usuarios` si MySQL está activo
            try {
                $pdo = DependencyInjection::getPdo();
                $updLegacy = $pdo->prepare("UPDATE usuarios SET clave = :clave WHERE email = :email OR id = :id");
                $updLegacy->execute(array(
                    ':clave' => $newPasswordVo->value(),
                    ':email' => $userModel->email()->value(),
                    ':id' => $userModel->id()->value(),
                ));
            } catch (Throwable) {
                // Modo InMemory
            }

            SecurityGuard::clearTemporaryPasswordFlag($logged['id']);
            unset($_SESSION['auth']['must_change_password']);

            Flash::setSuccess('¡Contraseña actualizada exitosamente! Ya puedes utilizar todas las funciones del sistema.');
            View::redirect('home');
            break;

        case 'emails.outbox':
            if (!isAdminUser()) {
                Flash::setMessage('Solo un Administrador puede inspeccionar la bandeja de auditoría de correos.');
                View::redirect('home');
            }
            $messages = DependencyInjection::getEmailSenderService()->listOutboxMessages(25);
            View::render('emails/outbox', array(
                'pageTitle' => 'Bandeja de salida de correos',
                'messages' => $messages,
            ));
            break;

        case 'smtp.config':
            if (!isAdminUser()) {
                Flash::setMessage('Solo un Administrador puede editar la configuración SMTP.');
                View::redirect('home');
            }
            View::render('emails/smtp-config', array(
                'pageTitle' => 'Configuración SMTP y Pasarela HTTPS 443',
                'smtp' => DependencyInjection::resolveEffectiveSmtpSettings(),
                'relay' => DependencyInjection::resolveEffectiveCloudRelaySettings(),
                'message' => Flash::message(),
                'success' => Flash::success(),
            ));
            break;

        case 'smtp.save':
            if (!isAdminUser()) {
                Flash::setMessage('Solo un Administrador puede editar la configuración SMTP.');
                View::redirect('home');
            }
            $smtpRepo = DependencyInjection::getSmtpConfigRepository();
            if ($smtpRepo === null) {
                Flash::setMessage('La tabla configuracion_smtp requiere almacenamiento MySQL activo.');
                View::redirect('smtp.config');
            }

            $host = trim((string) ($_POST['host'] ?? ''));
            $port = (int) ($_POST['port'] ?? 587);
            $username = trim((string) ($_POST['username'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $remitente = trim((string) ($_POST['remitente'] ?? ''));

            if ($host === '' || $port <= 0 || $port > 65535 || !filter_var($username, FILTER_VALIDATE_EMAIL)) {
                Flash::setMessage('Verifica servidor SMTP, puerto válido (1-65535) y correo autenticado.');
                View::redirect('smtp.config');
            }

            $smtpRepo->saveConfig($host, $port, $username, $password, $remitente);
            DependencyInjection::invalidateEmailSender();
            Flash::setSuccess('Configuración SMTP actualizada correctamente en la base de datos.');
            View::redirect('smtp.config');
            break;

        case 'smtp.relay.save':
            if (!isAdminUser()) {
                Flash::setMessage('Solo un Administrador puede editar la pasarela HTTPS 443.');
                View::redirect('home');
            }
            $smtpRepo = DependencyInjection::getSmtpConfigRepository();
            if ($smtpRepo === null) {
                Flash::setMessage('La tabla hex_cloud_email_relay requiere almacenamiento MySQL activo.');
                View::redirect('smtp.config');
            }

            $webhookUrl = trim((string) ($_POST['webhook_url'] ?? ''));
            $webhookToken = trim((string) ($_POST['webhook_token'] ?? ''));
            $brevoKey = trim((string) ($_POST['brevo_api_key'] ?? ''));
            $resendKey = trim((string) ($_POST['resend_api_key'] ?? ''));
            $httpsFrom = trim((string) ($_POST['https_from_address'] ?? ''));
            $clearSecrets = isset($_POST['clear_secrets']) && (string) $_POST['clear_secrets'] === '1';

            if ($webhookUrl !== '' && (!filter_var($webhookUrl, FILTER_VALIDATE_URL) || strtolower((string) parse_url($webhookUrl, PHP_URL_SCHEME)) !== 'https')) {
                Flash::setMessage('La URL del Webhook debe ser una dirección pública segura con esquema https://.');
                View::redirect('smtp.config');
            }

            if ($httpsFrom !== '' && !filter_var($httpsFrom, FILTER_VALIDATE_EMAIL)) {
                Flash::setMessage('El correo remitente para Brevo/Resend no tiene un formato válido.');
                View::redirect('smtp.config');
            }

            $smtpRepo->saveCloudRelayConfig($webhookUrl, $webhookToken, $brevoKey, $resendKey, $httpsFrom, $clearSecrets);
            DependencyInjection::invalidateEmailSender();
            Flash::setSuccess('Pasarela Cloud HTTPS 443 actualizada en caliente en MySQL (sin afectar configuracion_smtp).');
            View::redirect('smtp.config');
            break;

        case 'smtp.test':
            if (!isAdminUser()) {
                Flash::setMessage('Solo un Administrador puede ejecutar diagnósticos de envío.');
                View::redirect('home');
            }
            $testEmail = trim(strtolower((string) ($_POST['test_email'] ?? '')));
            if ($testEmail === '' || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                Flash::setMessage('Ingresa un correo electrónico destinatario válido para la prueba.');
                View::redirect('smtp.config');
            }

            DependencyInjection::invalidateEmailSender();
            $sender = DependencyInjection::getEmailSenderService();
            $sender->send(new EmailDestinationModel(
                $testEmail,
                'Prueba Diagnóstica',
                'Prueba de entrega — Control de Usuarios y Gastos (#6)',
                '<h2>Prueba de correo exitosa</h2><p>Este mensaje confirma la salida real de correo desde la arquitectura Hexagonal PHP 8.3.</p>'
            ));

            $ch = $sender->getLastDeliveryChannel();
            $err = $sender->getLastError();
            if ($ch === 'https_api' || $ch === 'smtp') {
                Flash::setSuccess("Envío real exitoso hacia {$testEmail} mediante el canal [{$ch}]. Revisa tu bandeja de entrada (y Spam).");
            } else {
                Flash::setMessage(
                    "El servidor no pudo salir por red externa (canal resultante: outbox). Detalle técnico: "
                    . ($err !== '' ? $err : 'Firewall Cloud bloquea puertos TCP 587/465 y no hay pasarela HTTPS 443 activa.')
                );
            }
            View::redirect('smtp.config');
            break;

        default:
            throw new RuntimeException('Acción no soportada.');
    }
} catch (Throwable $exception) {
    $msg = $exception->getMessage();
    Flash::setMessage($msg);

    switch ($route) {
        case 'users.store':
            Flash::setOld(SecurityGuard::sanitizeOldInput(getCreateUserFormData()));
            View::redirect('users.create');
            break;

        case 'users.update':
            $updateId = SecurityGuard::sanitizeHeaderValue(trim((string) ($_POST['id'] ?? '')));
            Flash::setOld(SecurityGuard::sanitizeOldInput(getUpdateUserFormData()));
            header('Location: ?route=users.edit&id=' . urlencode($updateId));
            exit;

        case 'gastos.store':
            Flash::setOld(SecurityGuard::sanitizeOldInput(getGastoFormData()));
            View::redirect('gastos.create');
            break;

        case 'gastos.update':
            $updateGastoId = SecurityGuard::sanitizeHeaderValue(trim((string) ($_POST['id'] ?? '')));
            Flash::setOld(SecurityGuard::sanitizeOldInput(getGastoFormData(true)));
            header('Location: ?route=gastos.edit&id=' . urlencode($updateGastoId));
            exit;

        case 'gastos.show':
        case 'gastos.edit':
        case 'gastos.delete':
            View::redirect('gastos.index');
            break;

        case 'auth.authenticate':
            Flash::setOld(array('email' => trim(strtolower((string) ($_POST['email'] ?? '')))));
            View::redirect('auth.login');
            break;

        case 'auth.forgot.send':
            Flash::setOld(array('email' => trim((string) ($_POST['email'] ?? ''))));
            View::redirect('auth.forgot');
            break;

        case 'users.show':
        case 'users.edit':
        case 'users.delete':
            View::redirect('users.index');
            break;

        default:
            View::render('home', buildHomeViewData($msg));
            break;
    }
}

// ──────────────────────────────────────────────────────────────
// Despachador API RESTful y Swagger OpenAPI
// ──────────────────────────────────────────────────────────────

/**
 * Guardia central de autenticación y revocación en vivo para la API RESTful:
 *   1. Controla inundación de peticiones por IP (`120 req / 60s` -> `HTTP 429`).
 *   2. Exige encabezado `Authorization: Bearer <JWT>` (`HTTP 401` si falta).
 *   3. Verifica criptográficamente firma `HMAC-SHA256`, `alg === HS256` y `exp`.
 *   4. Revalida contra el Dominio (`GetUserByIdUseCase`) que la cuenta siga existiendo
 *      y permanezca en estado `ACTIVE` (revocación inmediata de tokens huérfanos/bloqueados).
 */
function authenticateRestRequest(ApiRequest $request): UserModel
{
    ClassLoader::loadClass('GetUserByIdQuery');
    ClassLoader::loadClass('UserNotFoundException');
    ClassLoader::loadClass('UserStatusEnum');
    ClassLoader::loadClass('UserRoleEnum');

    $ipKey = SecurityGuard::clientIp();
    if (!SecurityGuard::checkRateLimit('api_global', $ipKey, 120, 60)) {
        throw new RuntimeException(
            'Demasiadas solicitudes a la API desde esta dirección IP. Espera un minuto antes de reintentar.',
            429
        );
    }
    SecurityGuard::recordFailedAttempt('api_global', $ipKey, 60);

    $bearerToken = $request->bearerToken();
    if ($bearerToken === null || $bearerToken === '') {
        throw new RuntimeException(
            'Acceso denegado: esta API exige un token Bearer JWT válido. Autentícate primero en POST /api/auth/login.',
            401
        );
    }

    $claims = SecurityGuard::verifyApiJwt($bearerToken);

    try {
        $liveUser = DependencyInjection::getGetUserByIdUseCase()->execute(
            new GetUserByIdQuery((string) $claims['sub'])
        );
    } catch (UserNotFoundException) {
        throw new RuntimeException(
            'Acceso no autorizado: la cuenta asociada al token JWT ya no existe.',
            401
        );
    }

    if ($liveUser->status() !== UserStatusEnum::ACTIVE) {
        throw new RuntimeException(
            'Acceso no autorizado: la cuenta asociada al token JWT no se encuentra ACTIVA.',
            401
        );
    }

    return $liveUser;
}

function dispatchRestApi(string $apiPath): never
{
    ClassLoader::loadClass('ApiRequest');
    ClassLoader::loadClass('ApiResponse');
    ClassLoader::loadClass('ErrorHandler');
    ClassLoader::loadClass('UserRoleEnum');

    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if ($method === 'OPTIONS') {
        (new ApiResponse(204, array()))->send();
        exit;
    }

    try {
        $request = ApiRequest::fromGlobals($apiPath);
        $trimmed = rtrim($apiPath, '/');

        // ── Únicos 2 endpoints públicos sin token: Health Check y Emisión JWT Login ──
        if ($trimmed === '/api/health' && $method === 'GET') {
            ApiResponse::success(array(
                'service' => 'users-gastos-hexagonal-ddd-cqrs',
                'status' => 'UP',
                'storage_driver' => DependencyInjection::getActiveStorageDriver(),
                'entities' => array('Usuario', 'Gasto'),
                'api_auth' => 'Bearer JWT (HS256) + RBAC',
                'security' => 'OWASP-Hardened',
                'timestamp' => date('c'),
            ))->send();
            exit;
        }

        if ($trimmed === '/api/auth/login') {
            if ($method === 'POST') {
                DependencyInjection::getUserRestController()->login($request)->send();
                exit;
            }
            ApiResponse::error('MethodNotAllowed', 'Usa POST /api/auth/login para obtener tu token JWT.', 405)->send();
            exit;
        }

        // ── Barrera Criptográfica Obligatoria JWT + Revocación en Vivo ──
        $authUser = authenticateRestRequest($request);
        $callerRole = $authUser->role();
        $callerId = $authUser->id()->value();

        if ($trimmed === '/api/tax-indicators' && $method === 'GET') {
            $indicators = DependencyInjection::getTaxIndicatorRestPort()->fetchCurrentTaxIndicators();
            ApiResponse::success($indicators)->send();
            exit;
        }

        if ($trimmed === '/api/users') {
            $ctrl = DependencyInjection::getUserRestController();
            if ($method === 'GET') {
                $ctrl->list()->send();
                exit;
            }
            if ($method === 'POST') {
                SecurityGuard::assertApiRole($callerRole, array(UserRoleEnum::ADMIN), 'registrar nuevos usuarios por API');
                $ctrl->create($request, generateUuid4())->send();
                exit;
            }
            ApiResponse::error('MethodNotAllowed', 'Método no permitido en /api/users', 405)->send();
            exit;
        }

        if (preg_match('#^/api/users/([^/]+)$#', $trimmed, $m)) {
            $id = urldecode($m[1]);
            $ctrl = DependencyInjection::getUserRestController();
            if ($method === 'GET') {
                $ctrl->getById($id)->send();
                exit;
            }
            if ($method === 'PUT' || $method === 'PATCH') {
                SecurityGuard::assertApiSelfOrAdmin($callerId, $callerRole, $id);
                $safeRequest = $callerRole === UserRoleEnum::ADMIN
                    ? $request
                    : $request->withBodyOverrides(array(
                        'role' => $authUser->role(),
                        'status' => $authUser->status(),
                    ));
                $ctrl->update($safeRequest, $id)->send();
                exit;
            }
            if ($method === 'DELETE') {
                SecurityGuard::assertApiRole($callerRole, array(UserRoleEnum::ADMIN), 'eliminar cuentas de usuario');
                SecurityGuard::assertNotSelfDeletion($callerId, $id);
                $ctrl->delete($id)->send();
                exit;
            }
            ApiResponse::error('MethodNotAllowed', 'Método no permitido en /api/users/{id}', 405)->send();
            exit;
        }

        if ($trimmed === '/api/gastos') {
            $ctrl = DependencyInjection::getGastoRestController();
            if ($method === 'GET') {
                $ctrl->list($request)->send();
                exit;
            }
            if ($method === 'POST') {
                SecurityGuard::assertApiRole(
                    $callerRole,
                    array(UserRoleEnum::ADMIN, UserRoleEnum::MEMBER),
                    'registrar nuevos gastos'
                );
                $ctrl->create($request, generateUuid4())->send();
                exit;
            }
            ApiResponse::error('MethodNotAllowed', 'Método no permitido en /api/gastos', 405)->send();
            exit;
        }

        if (preg_match('#^/api/gastos/([^/]+)$#', $trimmed, $m)) {
            $id = urldecode($m[1]);
            $ctrl = DependencyInjection::getGastoRestController();
            if ($method === 'GET') {
                $ctrl->getById($id)->send();
                exit;
            }
            if ($method === 'PUT' || $method === 'PATCH') {
                SecurityGuard::assertApiRole(
                    $callerRole,
                    array(UserRoleEnum::ADMIN, UserRoleEnum::MEMBER),
                    'actualizar gastos'
                );
                $ctrl->update($request, $id)->send();
                exit;
            }
            if ($method === 'DELETE') {
                SecurityGuard::assertApiRole(
                    $callerRole,
                    array(UserRoleEnum::ADMIN, UserRoleEnum::MEMBER),
                    'eliminar gastos'
                );
                $ctrl->delete($id)->send();
                exit;
            }
            ApiResponse::error('MethodNotAllowed', 'Método no permitido en /api/gastos/{id}', 405)->send();
            exit;
        }

        ApiResponse::error('RouteNotFound', 'Endpoint API no encontrado: ' . $apiPath, 404)->send();
    } catch (Throwable $e) {
        ErrorHandler::handle($e)->send();
    }
    exit;
}

function serveOpenApiSpec(): never
{
    SecurityGuard::applyHttpSecurityHeaders();
    $specFile = __DIR__ . '/../Infrastructure/Entrypoints/Rest/Swagger/openapi.json';
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    readfile($specFile);
    exit;
}

function serveSwaggerUi(): never
{
    SecurityGuard::applyHttpSecurityHeaders(true);
    header('Content-Type: text/html; charset=UTF-8');
    echo <<<'HTML'
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Swagger UI — CRUD Usuarios y Gastos (#6) · Protegido con JWT</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        window.onload = function () {
            SwaggerUIBundle({
                url: '?route=openapi',
                dom_id: '#swagger-ui',
                deepLinking: true,
                persistAuthorization: true
            });
        };
    </script>
</body>
</html>
HTML;
    exit;
}

function sendPasswordRecoveryEmail(string $email, string $name, string $tempPassword): string
{
    $templateFile = __DIR__ . '/../Infrastructure/Entrypoints/Web/Presentation/Views/emails/forgot-password.php';

    $scheme = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') ||
              (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'crud-de-usuarios-y-control-de-gastos-6.onrender.com');
    $scriptDir = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'))), '/');
    $baseUrl = $scheme . '://' . $host . ($scriptDir !== '' && $scriptDir !== '/' ? $scriptDir : '');
    $directLoginUrl = $baseUrl . '/?route=auth.login&email=' . urlencode($email);

    ob_start();
    extract(array(
        'email' => $email,
        'name' => $name,
        'tempPassword' => $tempPassword,
        'directLoginUrl' => $directLoginUrl,
    ), EXTR_SKIP);
    require $templateFile;
    $htmlBody = (string) ob_get_clean();

    ClassLoader::loadClass('EmailDestinationModel');

    $destination = new EmailDestinationModel(
        $email,
        $name,
        'Recuperación de contraseña',
        $htmlBody
    );

    $sender = DependencyInjection::getEmailSenderService();
    $sender->send($destination);

    return $sender->getLastDeliveryChannel();
}

/**
 * @param UserResponse[] $users
 * @return array<string, mixed>
 */
function buildListUsersViewData(array $users): array
{
    return array(
        'pageTitle' => 'Directorio de usuarios',
        'users' => $users,
        'message' => Flash::message(),
        'success' => Flash::success(),
    );
}

/**
 * @param GastoResponse[] $gastos
 * @param array<string, string> $filters
 * @return array<string, mixed>
 */
function buildListGastosViewData(array $gastos, array $filters): array
{
    $sinIva = 0.0;
    $iva = 0.0;
    $conIva = 0.0;

    foreach ($gastos as $g) {
        $sinIva += $g->getValorTotalSinIVA();
        $iva += $g->getIvaTotal();
        $conIva += $g->getValorTotalConIVA();
    }

    return array(
        'pageTitle' => 'Libro de gastos (#6)',
        'gastos' => $gastos,
        'filters' => $filters,
        'totals' => array(
            'sinIva' => $sinIva,
            'iva' => $iva,
            'conIva' => $conIva,
        ),
        'message' => Flash::message(),
        'success' => Flash::success(),
    );
}

/**
 * Construye los datos del Panel Principal incluyendo KPIs ejecutivos si hay sesión activa.
 *
 * @return array<string, mixed>
 */
function buildHomeViewData(string $message = ''): array
{
    $kpis = null;

    if (isLoggedIn()) {
        try {
            $allGastos = DependencyInjection::getGastoController()->index();
            $allUsers = DependencyInjection::getUserController()->index();
            $sinIva = 0.0;
            $iva = 0.0;
            $conIva = 0.0;
            foreach ($allGastos as $g) {
                $sinIva += $g->getValorTotalSinIVA();
                $iva += $g->getIvaTotal();
                $conIva += $g->getValorTotalConIVA();
            }
            $kpis = array(
                'gastosCount' => count($allGastos),
                'totalSinIva' => $sinIva,
                'totalIva' => $iva,
                'totalConIva' => $conIva,
                'usersCount' => count($allUsers),
                'driver' => DependencyInjection::getActiveStorageDriver(),
            );
        } catch (Throwable) {
            $kpis = null;
        }
    }

    return array(
        'pageTitle' => 'Panel Principal — Usuarios y Gastos (#6)',
        'message' => $message !== '' ? $message : Flash::message(),
        'success' => Flash::success(),
        'kpis' => $kpis,
    );
}

/**
 * Construye las proyecciones analíticas CQRS para `?route=reports`.
 *
 * @return array<string, mixed>
 */
function buildReportsViewData(): array
{
    $qLugar = isset($_GET['qLugar']) ? trim((string) $_GET['qLugar']) : '';
    $fechaDesde = isset($_GET['fechaDesde']) ? trim((string) $_GET['fechaDesde']) : '';
    $fechaHasta = isset($_GET['fechaHasta']) ? trim((string) $_GET['fechaHasta']) : '';
    $rolFiltro = isset($_GET['rolFiltro']) ? strtoupper(trim((string) $_GET['rolFiltro'])) : '';

    $gastoCtrl = DependencyInjection::getGastoController();
    $userCtrl = DependencyInjection::getUserController();

    $allGastos = $gastoCtrl->index();
    $allUsers = $userCtrl->index();

    $globalSinIva = 0.0;
    $globalIva = 0.0;
    $globalConIva = 0.0;
    $lugarBuckets = array();
    $userBuckets = array();
    $lugarLookup = array('count' => 0, 'sinIva' => 0.0, 'iva' => 0.0, 'conIva' => 0.0);
    $needleLugar = mb_strtolower($qLugar);

    foreach ($allGastos as $g) {
        $s = $g->getValorTotalSinIVA();
        $i = $g->getIvaTotal();
        $c = $g->getValorTotalConIVA();
        $globalSinIva += $s;
        $globalIva += $i;
        $globalConIva += $c;

        $lugarName = $g->getLugar();
        $lugarKey = mb_strtolower($lugarName);
        if (!isset($lugarBuckets[$lugarKey])) {
            $lugarBuckets[$lugarKey] = array('lugar' => $lugarName, 'count' => 0, 'sinIva' => 0.0, 'iva' => 0.0, 'conIva' => 0.0);
        }
        $lugarBuckets[$lugarKey]['count']++;
        $lugarBuckets[$lugarKey]['sinIva'] += $s;
        $lugarBuckets[$lugarKey]['iva'] += $i;
        $lugarBuckets[$lugarKey]['conIva'] += $c;

        if ($needleLugar !== '' && str_contains($lugarKey, $needleLugar)) {
            $lugarLookup['count']++;
            $lugarLookup['sinIva'] += $s;
            $lugarLookup['iva'] += $i;
            $lugarLookup['conIva'] += $c;
        }

        $uName = $g->getNombreUsuario();
        $uKey = mb_strtolower($uName);
        if (!isset($userBuckets[$uKey])) {
            $userBuckets[$uKey] = array('usuario' => $uName, 'count' => 0, 'sinIva' => 0.0, 'iva' => 0.0, 'conIva' => 0.0);
        }
        $userBuckets[$uKey]['count']++;
        $userBuckets[$uKey]['sinIva'] += $s;
        $userBuckets[$uKey]['iva'] += $i;
        $userBuckets[$uKey]['conIva'] += $c;
    }

    $byLugar = array_values($lugarBuckets);
    usort($byLugar, static fn(array $a, array $b): int => $b['conIva'] <=> $a['conIva']);
    foreach ($byLugar as &$lRow) {
        $lRow['share'] = $globalConIva > 0 ? ($lRow['conIva'] / $globalConIva) * 100.0 : 0.0;
    }
    unset($lRow);

    $byUser = array_values($userBuckets);
    usort($byUser, static fn(array $a, array $b): int => $b['conIva'] <=> $a['conIva']);
    foreach ($byUser as &$uRow) {
        $uRow['share'] = $globalConIva > 0 ? ($uRow['conIva'] / $globalConIva) * 100.0 : 0.0;
    }
    unset($uRow);

    $dateRangeGastos = ($fechaDesde !== '' || $fechaHasta !== '')
        ? $gastoCtrl->index(null, $fechaDesde !== '' ? $fechaDesde : null, $fechaHasta !== '' ? $fechaHasta : null)
        : $allGastos;

    $drSin = 0.0;
    $drIva = 0.0;
    $drCon = 0.0;
    foreach ($dateRangeGastos as $dg) {
        $drSin += $dg->getValorTotalSinIVA();
        $drIva += $dg->getIvaTotal();
        $drCon += $dg->getValorTotalConIVA();
    }

    $roleCounts = array(
        UserRoleEnum::ADMIN => 0,
        UserRoleEnum::MEMBER => 0,
        UserRoleEnum::REVIEWER => 0,
    );
    $filteredUsers = array();
    foreach ($allUsers as $u) {
        $r = $u->getRole();
        $roleCounts[$r] = ($roleCounts[$r] ?? 0) + 1;
        if ($rolFiltro === '' || $r === $rolFiltro) {
            $filteredUsers[] = $u;
        }
    }

    return array(
        'pageTitle' => 'Reportes Analíticos CQRS',
        'filters' => array(
            'qLugar' => $qLugar,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'rolFiltro' => $rolFiltro,
        ),
        'globalTotals' => array(
            'count' => count($allGastos),
            'sinIva' => $globalSinIva,
            'iva' => $globalIva,
            'conIva' => $globalConIva,
            'avgConIva' => count($allGastos) > 0 ? ($globalConIva / count($allGastos)) : 0.0,
            'usersCount' => count($allUsers),
        ),
        'lugarLookup' => $lugarLookup,
        'byLugar' => $byLugar,
        'byUser' => $byUser,
        'dateRangeGastos' => $dateRangeGastos,
        'dateRangeTotals' => array(
            'sinIva' => $drSin,
            'iva' => $drIva,
            'conIva' => $drCon,
        ),
        'roleCounts' => $roleCounts,
        'filteredUsers' => $filteredUsers,
    );
}

/**
 * @return array<string, mixed>
 */
function buildCreateUserViewData(): array
{
    return array(
        'pageTitle' => 'Registrar usuario',
        'roleOptions' => UserRoleEnum::values(),
        'canAssignRole' => isAdminUser(),
        'message' => Flash::message(),
        'success' => Flash::success(),
        'errors' => Flash::errors(),
        'old' => Flash::old(),
    );
}

/**
 * @return array<string, mixed>
 */
function buildCreateGastoViewData(): array
{
    $logged = getLoggedUser();
    $users = DependencyInjection::getUserController()->index();

    return array(
        'pageTitle' => 'Registrar gasto',
        'defaultUserName' => (string) ($logged['name'] ?? 'Administrador'),
        'registeredUsers' => $users,
        'message' => Flash::message(),
        'success' => Flash::success(),
        'errors' => Flash::errors(),
        'old' => Flash::old(),
    );
}

/**
 * @return array<string, mixed>
 */
function buildEditUserViewData(UserResponse $user): array
{
    return array(
        'pageTitle' => 'Editar usuario',
        'user' => $user,
        'roleOptions' => UserRoleEnum::values(),
        'statusOptions' => UserStatusEnum::values(),
        'canEditRoleAndStatus' => isAdminUser(),
        'message' => Flash::message(),
        'errors' => Flash::errors(),
        'old' => Flash::old(),
    );
}

function generateUuid4(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * @return array<string, string>
 */
function getCreateUserFormData(): array
{
    return array(
        'name' => isset($_POST['name']) ? trim((string) $_POST['name']) : '',
        'email' => isset($_POST['email']) ? trim((string) $_POST['email']) : '',
        'password' => isset($_POST['password']) ? trim((string) $_POST['password']) : '',
        'role' => isset($_POST['role']) ? trim((string) $_POST['role']) : UserRoleEnum::MEMBER,
    );
}

/**
 * @return array<string, string>
 */
function getUpdateUserFormData(): array
{
    return array(
        'id' => isset($_POST['id']) ? trim((string) $_POST['id']) : '',
        'name' => isset($_POST['name']) ? trim((string) $_POST['name']) : '',
        'email' => isset($_POST['email']) ? trim((string) $_POST['email']) : '',
        'password' => isset($_POST['password']) ? (string) $_POST['password'] : '',
        'role' => isset($_POST['role']) ? trim((string) $_POST['role']) : '',
        'status' => isset($_POST['status']) ? trim((string) $_POST['status']) : '',
    );
}

/**
 * @return array<string, string>
 */
function getGastoFormData(bool $includeId = false): array
{
    $data = array(
        'fecha' => isset($_POST['fecha']) ? trim((string) $_POST['fecha']) : '',
        'valorTotalSinIVA' => isset($_POST['valorTotalSinIVA']) ? trim((string) $_POST['valorTotalSinIVA']) : '',
        'ivaTotal' => isset($_POST['ivaTotal']) ? trim((string) $_POST['ivaTotal']) : '',
        'valorTotalConIVA' => isset($_POST['valorTotalConIVA']) ? trim((string) $_POST['valorTotalConIVA']) : '',
        'nombreUsuario' => isset($_POST['nombreUsuario']) ? trim((string) $_POST['nombreUsuario']) : '',
        'lugar' => isset($_POST['lugar']) ? trim((string) $_POST['lugar']) : '',
        'descripcion' => isset($_POST['descripcion']) ? trim((string) $_POST['descripcion']) : '',
    );

    if ($includeId) {
        $data['id'] = isset($_POST['id']) ? trim((string) $_POST['id']) : '';
    }

    return $data;
}

/**
 * @param array<string, string> $form
 * @return array<string, string>
 */
function validateCreateUserForm(array $form): array
{
    $errors = array();

    if ($form['name'] === '') {
        $errors['name'] = 'El nombre es obligatorio.';
    }

    if ($form['email'] === '') {
        $errors['email'] = 'El correo es obligatorio.';
    } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'El correo no tiene un formato válido.';
    }

    if ($form['password'] === '') {
        $errors['password'] = 'La contraseña es obligatoria.';
    } elseif (strlen($form['password']) < 8) {
        $errors['password'] = 'La contraseña debe tener al menos 8 caracteres.';
    }

    if ($form['role'] === '') {
        $errors['role'] = 'El rol es obligatorio.';
    }

    return $errors;
}

/**
 * @param array<string, string> $form
 * @return array<string, string>
 */
function validateUpdateUserForm(array $form): array
{
    $errors = array();

    if ($form['name'] === '') {
        $errors['name'] = 'El nombre es obligatorio.';
    }

    if ($form['email'] === '') {
        $errors['email'] = 'El correo es obligatorio.';
    } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'El correo no tiene un formato válido.';
    }

    if ($form['password'] !== '' && strlen($form['password']) < 8) {
        $errors['password'] = 'La contraseña debe tener al menos 8 caracteres si deseas cambiarla.';
    }

    if ($form['role'] === '') {
        $errors['role'] = 'El rol es obligatorio.';
    }

    if ($form['status'] === '') {
        $errors['status'] = 'El estado es obligatorio.';
    }

    return $errors;
}

/**
 * @param array<string, string> $form
 * @return array<string, string>
 */
function validateGastoForm(array $form): array
{
    $errors = array();

    if ($form['fecha'] === '') {
        $errors['fecha'] = 'La fecha del gasto es obligatoria.';
    }

    if ($form['valorTotalSinIVA'] === '' || !is_numeric($form['valorTotalSinIVA']) || (float) $form['valorTotalSinIVA'] <= 0) {
        $errors['valorTotalSinIVA'] = 'El valor total sin IVA debe ser numérico y mayor que cero.';
    }

    if ($form['ivaTotal'] === '' || !is_numeric($form['ivaTotal']) || (float) $form['ivaTotal'] < 0) {
        $errors['ivaTotal'] = 'El IVA total debe ser numérico y mayor o igual a cero.';
    }

    if ($form['valorTotalConIVA'] === '' || !is_numeric($form['valorTotalConIVA']) || (float) $form['valorTotalConIVA'] <= 0) {
        $errors['valorTotalConIVA'] = 'El valor total con IVA debe ser numérico y mayor que cero.';
    }

    if (mb_strlen($form['nombreUsuario']) < 3) {
        $errors['nombreUsuario'] = 'El nombre del usuario debe tener al menos 3 caracteres.';
    }

    if (mb_strlen($form['lugar']) < 3) {
        $errors['lugar'] = 'El lugar debe tener al menos 3 caracteres.';
    }

    if (mb_strlen($form['descripcion']) < 5) {
        $errors['descripcion'] = 'La descripción debe tener al menos 5 caracteres.';
    }

    return $errors;
}