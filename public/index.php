<?php

declare(strict_types=1);

// Soporte nativo para archivos físicos cuando se ejecuta con php -S localhost:8080 -t public public/index.php
if (PHP_SAPI === 'cli-server') {
    $cliPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $candidate = __DIR__ . $cliPath;
    if ($cliPath !== '/' && $cliPath !== '/index.php' && is_file($candidate)) {
        return false;
    }
}

// Entrega universal de hojas de estilo y scripts de presentación (compatible con Apache, Docker y php -S)
if (isset($_GET['asset'])) {
    $asset = (string) $_GET['asset'];
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
require_once __DIR__ . '/../Infrastructure/Entrypoints/Web/Presentation/View.php';
require_once __DIR__ . '/../Infrastructure/Entrypoints/Web/Presentation/Flash.php';

DependencyInjection::boot();

// ──────────────────────────────────────────────────────────────
// Resolución de ruta entrante para API REST y Swagger OpenAPI
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
// Guardia de seguridad Web (IIFE) — corregida para barras Windows/Linux
// ──────────────────────────────────────────────────────────────
(function () use ($rawUriPath, $normalizedScriptDir): void {
    $requestPath = rtrim(str_replace('\\', '/', $rawUriPath), '/');
    if ($normalizedScriptDir !== '' && $requestPath !== $normalizedScriptDir && !str_starts_with($requestPath, $normalizedScriptDir . '/')) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $dest = isset($_SESSION['auth']['id']) ? 'home' : 'auth.login';
        header('Location: ' . $normalizedScriptDir . '/index.php?route=' . $dest);
        exit;
    }
})();

Flash::start();

// ──────────────────────────────────────────────────────────────
// Auth helpers
// ──────────────────────────────────────────────────────────────
function isLoggedIn(): bool
{
    return isset($_SESSION['auth']['id']);
}

function getLoggedUser(): array
{
    return is_array($_SESSION['auth'] ?? null) ? $_SESSION['auth'] : array();
}

// ──────────────────────────────────────────────────────────────
// Enrutamiento Web (?route=)
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
    View::render('home', buildHomeViewData('Método HTTP no permitido.'));
    exit;
}

$publicActions = array(
    'home',
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

try {
    switch ($definition['action']) {
        case 'home':
            View::render('home', buildHomeViewData());
            break;

        case 'swagger':
            serveSwaggerUi();
            break;

        case 'openapi':
            serveOpenApiSpec();
            break;

        // ── CRUDL Usuarios ──
        case 'create':
            View::render('users/create', buildCreateUserViewData());
            break;

        case 'store':
            $controller = DependencyInjection::getUserController();
            $form = getCreateUserFormData();
            $form['id'] = generateUuid4();
            $errors = validateCreateUserForm($form);

            if (!empty($errors)) {
                Flash::setOld($form);
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
            $user = $controller->show($id);
            View::render('users/edit', buildEditUserViewData($user));
            break;

        case 'update':
            $controller = DependencyInjection::getUserController();
            $form = getUpdateUserFormData();
            $errors = validateUpdateUserForm($form);

            if (!empty($errors)) {
                Flash::setOld($form);
                Flash::setErrors($errors);
                Flash::setMessage('Corrige los errores del formulario.');
                header('Location: ?route=users.edit&id=' . urlencode($form['id']));
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

            Flash::setSuccess('Usuario actualizado correctamente.');
            View::redirect('users.index');
            break;

        case 'delete':
            $controller = DependencyInjection::getUserController();
            $id = isset($_POST['id']) ? trim((string) $_POST['id']) : '';
            $controller->delete($id);
            Flash::setSuccess('Usuario eliminado correctamente.');
            View::redirect('users.index');
            break;

        // ── CRUDL Gastos (Ejercicio #6) ──
        case 'gastos.create':
            View::render('gastos/create', buildCreateGastoViewData());
            break;

        case 'gastos.store':
            $gastoController = DependencyInjection::getGastoController();
            $form = getGastoFormData();
            $form['id'] = generateUuid4();
            $errors = validateGastoForm($form);

            if (!empty($errors)) {
                Flash::setOld($form);
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
                Flash::setOld($form);
                Flash::setErrors($errors);
                Flash::setMessage('Corrige los errores del formulario de gasto.');
                header('Location: ?route=gastos.edit&id=' . urlencode($form['id']));
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

        // ── Autenticación y Recuperación ──
        case 'login':
            if (isLoggedIn()) {
                View::redirect('home');
            }
            View::render('auth/login', array(
                'pageTitle' => 'Iniciar sesión',
                'message' => Flash::message(),
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

            $loginUseCase = DependencyInjection::getLoginUseCase();
            $command = new LoginCommand($email, $password);
            $user = $loginUseCase->execute($command);

            $_SESSION['auth'] = array(
                'id' => $user->id()->value(),
                'name' => $user->name()->value(),
                'email' => $user->email()->value(),
                'role' => $user->role(),
            );

            Flash::setSuccess('Bienvenido/a, ' . $user->name()->value() . '.');
            View::redirect('home');
            break;

        case 'logout':
            session_destroy();
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

                $repository->update($updatedUser);
            }

            if ($channelUsed === 'outbox') {
                Flash::setSuccess(
                    'Solicitud procesada correctamente. (Nota Cloud Free: si el proveedor bloquea puertos SMTP 587/465 como en Render Free, el correo HTML quedó registrado en "Bandeja correos" del panel administrativo o configure BREVO_API_KEY / RESEND_API_KEY por HTTPS 443).'
                );
            } else {
                Flash::setSuccess(
                    'Si el correo está registrado y la cuenta está activa, recibirás un mensaje con tu contraseña temporal.'
                );
            }
            View::redirect('auth.forgot');
            break;

        case 'emails.outbox':
            $messages = DependencyInjection::getEmailSenderService()->listOutboxMessages(25);
            View::render('emails/outbox', array(
                'pageTitle' => 'Bandeja de salida de correos',
                'messages' => $messages,
            ));
            break;

        case 'smtp.config':
            $logged = getLoggedUser();
            if (($logged['role'] ?? '') !== UserRoleEnum::ADMIN) {
                Flash::setMessage('Solo un Administrador puede editar la configuración SMTP.');
                View::redirect('home');
            }
            View::render('emails/smtp-config', array(
                'pageTitle' => 'Configuración SMTP',
                'smtp' => DependencyInjection::resolveEffectiveSmtpSettings(),
                'message' => Flash::message(),
                'success' => Flash::success(),
            ));
            break;

        case 'smtp.save':
            $logged = getLoggedUser();
            if (($logged['role'] ?? '') !== UserRoleEnum::ADMIN) {
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

            if ($host === '' || $port <= 0 || !filter_var($username, FILTER_VALIDATE_EMAIL)) {
                Flash::setMessage('Verifica servidor SMTP, puerto válido y correo autenticado.');
                View::redirect('smtp.config');
            }

            $smtpRepo->saveConfig($host, $port, $username, $password, $remitente);
            DependencyInjection::invalidateEmailSender();
            Flash::setSuccess('Configuración SMTP actualizada correctamente en la base de datos.');
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
            Flash::setOld(getCreateUserFormData());
            View::redirect('users.create');
            break;

        case 'users.update':
            $updateId = trim((string) ($_POST['id'] ?? ''));
            Flash::setOld(getUpdateUserFormData());
            header('Location: ?route=users.edit&id=' . urlencode($updateId));
            exit;

        case 'gastos.store':
            Flash::setOld(getGastoFormData());
            View::redirect('gastos.create');
            break;

        case 'gastos.update':
            $updateGastoId = trim((string) ($_POST['id'] ?? ''));
            Flash::setOld(getGastoFormData(true));
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

function dispatchRestApi(string $apiPath): never
{
    ClassLoader::loadClass('ApiRequest');
    ClassLoader::loadClass('ApiResponse');
    ClassLoader::loadClass('ErrorHandler');

    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if ($method === 'OPTIONS') {
        (new ApiResponse(204, array()))->send();
        exit;
    }

    try {
        $request = ApiRequest::fromGlobals($apiPath);
        $trimmed = rtrim($apiPath, '/');

        if ($trimmed === '/api/health' && $method === 'GET') {
            ApiResponse::success(array(
                'service' => 'users-gastos-hexagonal-ddd-cqrs',
                'php_version' => PHP_VERSION,
                'storage_driver' => DependencyInjection::getActiveStorageDriver(),
                'entities' => array('Usuario', 'Gasto'),
                'timestamp' => date('c'),
            ))->send();
            exit;
        }

        if ($trimmed === '/api/tax-indicators' && $method === 'GET') {
            $indicators = DependencyInjection::getTaxIndicatorRestPort()->fetchCurrentTaxIndicators();
            ApiResponse::success($indicators)->send();
            exit;
        }

        if ($trimmed === '/api/auth/login' && $method === 'POST') {
            DependencyInjection::getUserRestController()->login($request)->send();
            exit;
        }

        if ($trimmed === '/api/users') {
            $ctrl = DependencyInjection::getUserRestController();
            if ($method === 'GET') {
                $ctrl->list()->send();
                exit;
            }
            if ($method === 'POST') {
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
                $ctrl->update($request, $id)->send();
                exit;
            }
            if ($method === 'DELETE') {
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
                $ctrl->update($request, $id)->send();
                exit;
            }
            if ($method === 'DELETE') {
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
    $specFile = __DIR__ . '/../Infrastructure/Entrypoints/Rest/Swagger/openapi.json';
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    readfile($specFile);
    exit;
}

function serveSwaggerUi(): never
{
    header('Content-Type: text/html; charset=UTF-8');
    echo <<<'HTML'
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Swagger UI — CRUD Usuarios y Gastos (#6)</title>
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
                deepLinking: true
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

    ob_start();
    extract(array('email' => $email, 'name' => $name, 'tempPassword' => $tempPassword), EXTR_SKIP);
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
        'pageTitle' => 'Lista de usuarios',
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
        'pageTitle' => 'Lista de gastos',
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
 * @return array<string, mixed>
 */
function buildHomeViewData(string $message = ''): array
{
    return array(
        'pageTitle' => 'Menú principal',
        'message' => $message !== '' ? $message : Flash::message(),
        'success' => Flash::success(),
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
        'role' => isset($_POST['role']) ? trim((string) $_POST['role']) : '',
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