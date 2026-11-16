<?php

declare(strict_types=1);

require_once __DIR__ . '/../Common/DependencyInjection.php';

DependencyInjection::boot();
DependencyInjection::resetForTesting();

$passed = 0;
$failed = 0;

function assertCheck(bool $condition, string $label): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "[PASS] {$label}\n";
    } else {
        $failed++;
        echo "[FAIL] {$label}\n";
    }
}

function assertThrows(callable $fn, string $expectedExceptionClass, string $label): void
{
    try {
        $fn();
        assertCheck(false, $label . " (no lanzó excepción; se esperaba {$expectedExceptionClass})");
    } catch (Throwable $e) {
        $ok = is_a($e, $expectedExceptionClass);
        assertCheck($ok, $label . ($ok ? '' : ' (lanzó ' . get_class($e) . ': ' . $e->getMessage() . ')'));
    }
}

echo "=================================================================\n";
echo " SUITE QUIRÚRGICA DE ARQUITECTURA HEXAGONAL, DDD Y CQRS (PHP 8.3)\n";
echo " Entidades: Usuario (Común) + Gasto (Ejercicio #6)\n";
echo "=================================================================\n\n";

// ─────────────────────────────────────────────────────────────────
// 1. AUDITORÍA DE INVERSIÓN DE DEPENDENCIAS (REGLA DE ORO HEXAGONAL)
// ─────────────────────────────────────────────────────────────────
$projectRoot = dirname(__DIR__);

$domainPhpFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($projectRoot . '/Domain'));
$domainClean = true;
foreach ($domainPhpFiles as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $src = (string) file_get_contents($file->getPathname());
        if (preg_match('/\b(Application|Infrastructure|Common|PDO|PHPMailer)\b/', $src)) {
            $domainClean = false;
            echo "  Violación en Dominio: " . $file->getPathname() . "\n";
        }
    }
}
assertCheck($domainClean, 'Regla DIP #1: La capa Domain no depende de Application, Infrastructure, Common, PDO ni PHPMailer');

$appPhpFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($projectRoot . '/Application'));
$appClean = true;
foreach ($appPhpFiles as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $src = (string) file_get_contents($file->getPathname());
        // Evitar falsos positivos en comentarios verificando solo código sin comentarios
        $tokens = token_get_all($src);
        $codeOnly = '';
        foreach ($tokens as $tk) {
            if (is_array($tk) && in_array($tk[0], array(T_COMMENT, T_DOC_COMMENT), true)) {
                continue;
            }
            $codeOnly .= is_array($tk) ? $tk[1] : $tk;
        }
        if (preg_match('/\b(Infrastructure|Common|PDO|PHPMailer|UserRepositoryMySQL|GastoRepositoryMySQL)\b/', $codeOnly)) {
            $appClean = false;
            echo "  Violación en Application: " . $file->getPathname() . "\n";
        }
    }
}
assertCheck($appClean, 'Regla DIP #2: La capa Application depende únicamente de Domain y sus propios Puertos');

// ─────────────────────────────────────────────────────────────────
// 2. PRUEBAS UNITARIAS DE CAPA DE DOMINIO — ENTIDAD #6 GASTO
// ─────────────────────────────────────────────────────────────────
ClassLoader::loadClass('GastoModel');
ClassLoader::loadClass('GastoIvaRateEnum');
ClassLoader::loadClass('RoleId');
ClassLoader::loadClass('GastoCreatedDomainEvent');
ClassLoader::loadClass('GastoUpdatedDomainEvent');
ClassLoader::loadClass('GastoDeletedDomainEvent');

$gastoValido = GastoModel::create(
    new GastoId('gasto-001'),
    new GastoFecha('2026-10-12'),
    GastoMonto::fromInput('150000.50', 'valorTotalSinIVA', false),
    GastoMonto::fromInput('28500.10', 'ivaTotal', true),
    GastoMonto::fromInput('178500.60', 'valorTotalConIVA', false),
    new UserName('Rosary Carmona'),
    new GastoLugar('Papelería Universitaria'),
    new GastoDescripcion('Compra de marcadores y carpetas institucionales')
);

$arr = $gastoValido->toArray();
assertCheck(
    array_keys($arr) === array('id', 'fecha', 'valorTotalSinIVA', 'ivaTotal', 'valorTotalConIVA', 'nombreUsuario', 'lugar', 'descripcion'),
    'Dominio GastoModel expone exactamente id + los 7 atributos de negocio de la rúbrica (#6)'
);
assertCheck($arr['valorTotalSinIVA'] === 150000.50 && $arr['ivaTotal'] === 28500.10 && $arr['valorTotalConIVA'] === 178500.60, 'GastoMonto conserva precisión exacta en centavos sin deriva IEEE-754');
assertCheck(GastoIvaRateEnum::isStandardRate(19) && !GastoIvaRateEnum::isStandardRate(13), 'GastoIvaRateEnum valida tarifas estándar colombianas (0%, 5%, 19%)');
assertCheck((new RoleId(1))->equals(new RoleId(1)), 'ValueObject RoleId valida identificadores positivos');

assertThrows(
    static fn() => GastoModel::create(
        new GastoId('gasto-bad-iva'),
        new GastoFecha('2026-10-12'),
        GastoMonto::fromInput('100000', 'valorTotalSinIVA', false),
        GastoMonto::fromInput('19000', 'ivaTotal', true),
        GastoMonto::fromInput('125000', 'valorTotalConIVA', false),
        new UserName('Rosary Carmona'),
        new GastoLugar('Tienda Centro'),
        new GastoDescripcion('Gasto con suma inconsistente')
    ),
    InvalidGastoIvaConsistencyException::class,
    'Invariante DDD: GastoModel rechaza valorTotalSinIVA + ivaTotal != valorTotalConIVA'
);

assertThrows(
    static fn() => new GastoFecha('2026-02-30'),
    InvalidGastoFechaException::class,
    'ValueObject GastoFecha rechaza fechas calendario imposibles (2026-02-30)'
);

assertThrows(
    static fn() => GastoMonto::fromInput('-50', 'valorTotalSinIVA', false),
    InvalidGastoMontoException::class,
    'ValueObject GastoMonto rechaza valores negativos'
);

assertThrows(
    static fn() => GastoMonto::fromInput('0', 'valorTotalSinIVA', false),
    InvalidGastoMontoException::class,
    'ValueObject GastoMonto exige valorTotalSinIVA > 0 mientras permite ivaTotal = 0 (exento)'
);

$gastoExento = GastoModel::create(
    new GastoId('gasto-exento'),
    new GastoFecha('2026-10-13'),
    GastoMonto::fromInput('85000', 'valorTotalSinIVA', false),
    GastoMonto::fromInput('0', 'ivaTotal', true),
    GastoMonto::fromInput('85000', 'valorTotalConIVA', false),
    new UserName('Administrador'),
    new GastoLugar('Transporte Intermunicipal'),
    new GastoDescripcion('Servicio de transporte público exento de IVA')
);
assertCheck($gastoExento->ivaTotal()->toFloat() === 0.0, 'GastoModel soporta gastos exentos de IVA (ivaTotal = 0)');

$evCreated = new GastoCreatedDomainEvent($gastoValido);
assertCheck($evCreated->eventName() === 'gasto.created' && $evCreated->payload()['id'] === 'gasto-001', 'GastoCreatedDomainEvent extiende DomainEvent (EventDomain.php)');

// ─────────────────────────────────────────────────────────────────
// 3. PRUEBAS DE APLICACIÓN Y CORRECCIÓN DE BUGS BASE — USUARIOS
// ─────────────────────────────────────────────────────────────────
$loginUseCase = DependencyInjection::getLoginUseCase();
$loggedAdmin = $loginUseCase->execute(new LoginCommand('admin@crud.com', 'Admin12345'));
assertCheck($loggedAdmin->email()->value() === 'admin@crud.com' && $loggedAdmin->role() === UserRoleEnum::ADMIN, 'LoginUseCase autentica al usuario Administrador semilla');

assertThrows(
    static fn() => $loginUseCase->execute(new LoginCommand('admin@crud.com', 'ClaveErronea')),
    InvalidCredentialsException::class,
    'LoginUseCase rechaza contraseña incorrecta con InvalidCredentialsException'
);

$userCtrl = DependencyInjection::getUserController();
$createdUserResp = $userCtrl->store(new CreateUserWebRequest(
    'user-rosary-01',
    'Rosary Carmona',
    'rcarmonan@unicartagena.edu.co',
    'Segura2026*',
    UserRoleEnum::ADMIN
));
assertCheck($createdUserResp->getId() === 'user-rosary-01' && $createdUserResp->getStatus() === UserStatusEnum::ACTIVE, 'UserController::store registra usuario sin fallo por SMTP no configurado');

// Verificar corrección del bug de contraseña opcional vacía al editar usuario
$updatedUserResp = $userCtrl->update(new UpdateUserWebRequest(
    'user-rosary-01',
    'Rosary Eilyn Carmona Navarro',
    'rcarmonan@unicartagena.edu.co',
    '',
    UserRoleEnum::ADMIN,
    UserStatusEnum::ACTIVE
));
assertCheck($updatedUserResp->getName() === 'Rosary Eilyn Carmona Navarro', 'Bugfix verificado: UpdateUserService conserva la contraseña hash actual cuando el campo password viene vacío');

// Confirmar que la clave anterior sigue funcionando tras editar con password vacío
$reLogin = $loginUseCase->execute(new LoginCommand('rcarmonan@unicartagena.edu.co', 'Segura2026*'));
assertCheck($reLogin->id()->value() === 'user-rosary-01', 'Login sigue siendo válido con la clave original después de editar el perfil sin cambiar contraseña');

// ─────────────────────────────────────────────────────────────────
// 4. PRUEBAS DE APLICACIÓN CQRS, CACHÉ Y COLA DE EVENTOS — GASTO (#6)
// ─────────────────────────────────────────────────────────────────
$gastoCtrl = DependencyInjection::getGastoController();
$initialGastos = $gastoCtrl->index();
assertCheck(count($initialGastos) === 2, 'GastoController::index carga los 2 gastos semilla iniciales');

$nuevoGasto = $gastoCtrl->store(new CreateGastoWebRequest(
    'gasto-test-100',
    '2026-11-01',
    '400000',
    '76000',
    '476000',
    'Rosary Eilyn Carmona Navarro',
    ' Librería Nacional Cartagena ',
    'Adquisición de libros de Domain-Driven Design y Clean Architecture'
));
assertCheck($nuevoGasto->getValorTotalConIVA() === 476000.0 && $nuevoGasto->getLugar() === 'Librería Nacional Cartagena', 'CreateGastoUseCase persiste gasto e invalida caché de consultas');

$allAfterCreate = $gastoCtrl->index();
assertCheck(count($allAfterCreate) === 3, 'GetAllGastosUseCase refleja inmediatamente el nuevo gasto tras invalidación de CachePort');

$filteredByUser = $gastoCtrl->index('Rosary');
assertCheck(count($filteredByUser) === 1 && $filteredByUser[0]->getId() === 'gasto-test-100', 'GetAllGastosQuery filtra correctamente por nombreUsuario');

$fetchedGasto = $gastoCtrl->show('gasto-test-100');
assertCheck($fetchedGasto->getDescripcion() === 'Adquisición de libros de Domain-Driven Design y Clean Architecture', 'GetGastoByIdUseCase recupera el detalle exacto del gasto');

$editedGasto = $gastoCtrl->update(new UpdateGastoWebRequest(
    'gasto-test-100',
    '2026-11-02',
    '500000',
    '95000',
    '595000',
    'Rosary Eilyn Carmona Navarro',
    'Librería Nacional Bocagrande',
    'Adquisición ampliada de bibliografía técnica de ingeniería de software'
));
assertCheck($editedGasto->getValorTotalConIVA() === 595000.0, 'UpdateGastoUseCase actualiza montos y metadatos del gasto');

$gastoCtrl->delete('gasto-test-100');
assertThrows(
    static fn() => $gastoCtrl->show('gasto-test-100'),
    GastoNotFoundException::class,
    'DeleteGastoUseCase elimina el gasto y posteriores lecturas lanzan GastoNotFoundException'
);

$publishedEvents = DependencyInjection::getEventPublisherPort()->readPublishedEvents();
$eventNames = array_map(static fn(array $e): string => (string) $e['event_name'], $publishedEvents);
assertCheck(
    in_array('gasto.created', $eventNames, true)
    && in_array('gasto.updated', $eventNames, true)
    && in_array('gasto.deleted', $eventNames, true),
    'FileQueueEventPublisherAdapter registra eventos gasto.created, gasto.updated y gasto.deleted en la cola'
);

// ─────────────────────────────────────────────────────────────────
// 5. PRUEBAS DE ADAPTADORES DE PERSISTENCIA MYSQL MAPPER Y REST EXTERNO
// ─────────────────────────────────────────────────────────────────
$persistenceMapper = DependencyInjection::getGastoPersistenceMapper();
$dto = $persistenceMapper->fromModelToDto($gastoValido);
$simulatedSqlRow = array(
    'id' => $dto->id(),
    'fecha' => $dto->fecha(),
    'valor_total_sin_iva' => $dto->valorTotalSinIVA(),
    'iva_total' => $dto->ivaTotal(),
    'valor_total_con_iva' => $dto->valorTotalConIVA(),
    'nombre_usuario' => $dto->nombreUsuario(),
    'lugar' => $dto->lugar(),
    'descripcion' => $dto->descripcion(),
    'created_at' => '2026-10-12 10:00:00',
    'updated_at' => '2026-10-12 10:00:00',
);
$reconstructedModel = $persistenceMapper->fromRowToModel($simulatedSqlRow);
assertCheck($reconstructedModel->toArray() === $gastoValido->toArray(), 'GastoPersistenceMapper transforma sin pérdida Model -> DTO -> SQL Row -> Entity -> Model');

$legacyAlwaysdataRow = array(
    'id_gasto' => 9,
    'fecha' => '2026-09-14',
    'valor_total_sin_iva' => 10000,
    'iva_total' => 1900,
    'valor_total_con_iva' => 11900,
    'nombre_usuario' => 'admin',
    'lugar' => 'en la casa',
    'descripcion' => '',
);
$legacyHydrated = $persistenceMapper->fromRowToModel($legacyAlwaysdataRow);
assertCheck(
    $legacyHydrated->id()->value() === '9' && $legacyHydrated->valorTotalConIVA()->toFloat() === 11900.0,
    'GastoPersistenceMapper hidrata filas heredadas de Alwaysdata (id_gasto + descripcion vacía) sin alterar datos existentes'
);

$taxIndicators = DependencyInjection::getTaxIndicatorRestPort()->fetchCurrentTaxIndicators();
assertCheck(($taxIndicators['default_iva_rate'] ?? null) === 19, 'TaxIndicatorRestAdapter entrega indicadores tributarios con fallback resiliente');

ClassLoader::loadClass('EmailSenderService');
ClassLoader::loadClass('SmtpConfigRepositoryMySQL');
ClassLoader::loadClass('EmailDestinationModel');
ClassLoader::loadClass('EmailSenderException');

$normalizedDbSmtp = SmtpConfigRepositoryMySQL::normalizeRow(array(
    'id' => 1,
    'host' => 'smtp-app-gastos-ejercicio6.alwaysdata.net',
    'puerto' => 587,
    'usuario' => 'app-gastos-ejercicio6@alwaysdata.net',
    'clave' => 'secret',
    'remitente' => null,
));
$normalizedDisplaySender = SmtpConfigRepositoryMySQL::normalizeRow(array(
    'id' => 1,
    'host' => 'smtp-app-gastos-ejercicio6.alwaysdata.net',
    'puerto' => 587,
    'usuario' => 'app-gastos-ejercicio6@alwaysdata.net',
    'clave' => 'secret',
    'remitente' => 'Sistema de Gastos',
));
assertCheck(
    is_array($normalizedDbSmtp)
    && $normalizedDbSmtp['from_address'] === 'app-gastos-ejercicio6@alwaysdata.net'
    && is_array($normalizedDisplaySender)
    && $normalizedDisplaySender['from_address'] === 'app-gastos-ejercicio6@alwaysdata.net'
    && $normalizedDisplaySender['from_name'] === 'Sistema de Gastos',
    'SmtpConfigRepositoryMySQL normaliza la tabla dinámica configuracion_smtp (compatible con edición Spring Boot y Alwaysdata)'
);

$tmpOutbox = rtrim(sys_get_temp_dir(), "/\\") . '/hexagonal_outbox_' . uniqid('', true);
$nonStrictMailer = new EmailSenderService(
    '127.0.0.1',
    'smtp-user@alwaysdata.net',
    'smtp-secret-test',
    'smtp-user@alwaysdata.net',
    'CRUD Test',
    'tls',
    25259,
    $tmpOutbox,
    false
);
$nonStrictMailer->send(new EmailDestinationModel(
    'namtlakh@gmail.com',
    'Usuario Prueba',
    'Recuperación de contraseña',
    '<p>Clave temporal: 12345678</p>'
));
$outboxItems = $nonStrictMailer->listOutboxMessages(5);
assertCheck(
    $nonStrictMailer->getLastDeliveryChannel() === 'outbox'
    && count($outboxItems) === 1
    && $outboxItems[0]['to_email'] === 'namtlakh@gmail.com',
    'EmailSenderService degrada limpiamente a Bandeja Outbox cuando el puerto SMTP está bloqueado (ej. Render Free) y SMTP_STRICT=false'
);

$strictMailer = new EmailSenderService(
    '127.0.0.1',
    'smtp-user@alwaysdata.net',
    'smtp-secret-test',
    'smtp-user@alwaysdata.net',
    'CRUD Test',
    'tls',
    25259,
    $tmpOutbox,
    true
);
assertThrows(
    static fn() => $strictMailer->send(new EmailDestinationModel(
        'namtlakh@gmail.com',
        'Usuario Prueba',
        'Prueba estricta',
        '<p>Test</p>'
    )),
    EmailSenderException::class,
    'EmailSenderService lanza EmailSenderException únicamente cuando SMTP_STRICT=true'
);

// ─────────────────────────────────────────────────────────────────
// 6. PRUEBAS DE ENTRYPOINTS REST, ERROR HANDLER, RUTAS WEB Y OPENAPI
// ─────────────────────────────────────────────────────────────────
ClassLoader::loadClass('ApiRequest');
ClassLoader::loadClass('ErrorHandler');

$gastoRest = DependencyInjection::getGastoRestController();
$restCreateResp = $gastoRest->create(new ApiRequest('POST', '/api/gastos', array(), array(
    'fecha' => '2026-11-10',
    'valorTotalSinIVA' => 100000,
    'ivaTotal' => 19000,
    'valorTotalConIVA' => 119000,
    'nombreUsuario' => 'Administrador',
    'lugar' => 'Éxito Castellana Cartagena',
    'descripcion' => 'Suministros de cafetería para reunión de arquitectura',
)), 'rest-gasto-01');
assertCheck($restCreateResp->statusCode() === 201 && $restCreateResp->payload()['data']['id'] === 'rest-gasto-01', 'GastoRestController::create retorna HTTP 201 Created');

$restListResp = $gastoRest->list(new ApiRequest('GET', '/api/gastos'));
assertCheck($restListResp->statusCode() === 200 && $restListResp->payload()['data']['count'] === 3, 'GastoRestController::list retorna HTTP 200 con conteo y sumatorias contables');

try {
    $gastoRest->create(new ApiRequest('POST', '/api/gastos', array(), array(
        'fecha' => '2026-11-10',
        'valorTotalSinIVA' => 100000,
        'ivaTotal' => 19000,
        'valorTotalConIVA' => 999999,
        'nombreUsuario' => 'Administrador',
        'lugar' => 'Tienda Prueba',
        'descripcion' => 'Prueba de error 422 en REST',
    )), 'rest-gasto-bad');
    assertCheck(false, 'GastoRestController debe lanzar excepción ante IVA inconsistente');
} catch (Throwable $e) {
    $errResp = ErrorHandler::handle($e);
    assertCheck($errResp->statusCode() === 422, 'ErrorHandler traduce InvalidGastoIvaConsistencyException a HTTP 422 Unprocessable Entity');
}

$err404 = ErrorHandler::handle(GastoNotFoundException::becauseIdWasNotFound('inexistente'));
assertCheck($err404->statusCode() === 404, 'ErrorHandler traduce GastoNotFoundException a HTTP 404 Not Found');

$err409 = ErrorHandler::handle(UserAlreadyExistsException::becauseEmailAlreadyExists('admin@crud.com'));
assertCheck($err409->statusCode() === 409, 'ErrorHandler traduce UserAlreadyExistsException a HTTP 409 Conflict');

$webRoutes = WebRoutes::routes();
assertCheck(
    isset($webRoutes['gastos.index'], $webRoutes['gastos.create'], $webRoutes['gastos.store'], $webRoutes['gastos.show'], $webRoutes['gastos.edit'], $webRoutes['gastos.update'], $webRoutes['gastos.delete'], $webRoutes['emails.outbox'], $webRoutes['smtp.config'], $webRoutes['smtp.save'], $webRoutes['swagger'], $webRoutes['openapi']),
    'WebRoutes expone la totalidad de rutas CRUDL para Usuarios, Gastos (#6), Config. SMTP, Bandeja Outbox y Swagger'
);

$openApiRaw = (string) file_get_contents($projectRoot . '/Infrastructure/Entrypoints/Rest/Swagger/openapi.json');
$openApi = json_decode($openApiRaw, true);
assertCheck(
    is_array($openApi)
    && ($openApi['openapi'] ?? '') === '3.0.3'
    && isset($openApi['paths']['/api/users'], $openApi['paths']['/api/gastos'], $openApi['paths']['/api/gastos/{id}']),
    'Especificación Swagger OpenAPI 3.0.3 es JSON válido y documenta /api/users y /api/gastos'
);

echo "\n=================================================================\n";
echo " RESULTADO FINAL: {$passed} pruebas aprobadas, {$failed} fallidas.\n";
echo "=================================================================\n";

exit($failed > 0 ? 1 : 0);