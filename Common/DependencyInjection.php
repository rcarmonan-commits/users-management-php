<?php

declare(strict_types=1);

require_once __DIR__ . '/ClassLoader.php';
require_once __DIR__ . '/EnvLoader.php';

final class DependencyInjection
{
    private static ?PDO $sharedPdo = null;
    private static ?bool $mysqlAvailable = null;
    private static ?InMemoryUserRepository $inMemoryUserRepository = null;
    private static ?InMemoryGastoRepository $inMemoryGastoRepository = null;
    private static ?FileCacheAdapter $cacheAdapter = null;
    private static ?FileQueueEventPublisherAdapter $eventPublisher = null;
    private static ?EmailSenderService $emailSender = null;

    public static function boot(): void
    {
        EnvLoader::load(dirname(__DIR__) . '/.env');
        ClassLoader::register();
    }

    public static function resetForTesting(?InMemoryUserRepository $userRepo = null, ?InMemoryGastoRepository $gastoRepo = null): void
    {
        ClassLoader::loadClass('InMemoryUserRepository');
        ClassLoader::loadClass('InMemoryGastoRepository');
        ClassLoader::loadClass('FileCacheAdapter');
        ClassLoader::loadClass('FileQueueEventPublisherAdapter');
        ClassLoader::loadClass('EmailSenderService');

        self::$mysqlAvailable = false;
        self::$inMemoryUserRepository = $userRepo ?? new InMemoryUserRepository(true, null);
        self::$inMemoryGastoRepository = $gastoRepo ?? new InMemoryGastoRepository(true, null);
        $suffix = getmypid() . '_' . uniqid('', true);
        self::$cacheAdapter = new FileCacheAdapter(sys_get_temp_dir() . '/gastos_test_cache_' . $suffix);
        self::$eventPublisher = new FileQueueEventPublisherAdapter(sys_get_temp_dir() . '/gastos_test_queue_' . $suffix . '.jsonl');
        self::$emailSender = new EmailSenderService('', '', '', '', 'Test', 'tls', 587, sys_get_temp_dir() . '/gastos_test_emails_' . $suffix, false);
    }

    public static function getActiveStorageDriver(): string
    {
        return self::shouldUseMySql() ? 'mysql' : 'in_memory';
    }

    public static function getConnection(): Connection
    {
        ClassLoader::loadClass('Connection');

        return new Connection(
            host: EnvLoader::get('DB_HOST', '127.0.0.1'),
            port: EnvLoader::getInt('DB_PORT', 3306),
            database: EnvLoader::get('DB_DATABASE', 'crud_usuarios'),
            username: EnvLoader::get('DB_USERNAME', 'root'),
            password: EnvLoader::get('DB_PASSWORD', ''),
            charset: EnvLoader::get('DB_CHARSET', 'utf8mb4'),
            sslCa: EnvLoader::get('DB_SSL_CA', '')
        );
    }

    public static function getPdo(): PDO
    {
        if (self::$sharedPdo === null) {
            self::$sharedPdo = self::getConnection()->createPdo();
        }

        return self::$sharedPdo;
    }

    private static function shouldUseMySql(): bool
    {
        if (self::$mysqlAvailable !== null) {
            return self::$mysqlAvailable;
        }

        $driver = strtolower(EnvLoader::get('DB_DRIVER', 'auto'));
        if ($driver === 'memory' || $driver === 'in_memory') {
            self::$mysqlAvailable = false;
            return false;
        }

        if ($driver === 'mysql') {
            self::getPdo();
            self::$mysqlAvailable = true;
            return true;
        }

        if (!extension_loaded('pdo_mysql')) {
            self::$mysqlAvailable = false;
            return false;
        }

        try {
            self::getPdo();
            self::$mysqlAvailable = true;
        } catch (Throwable) {
            self::$mysqlAvailable = false;
        }

        return self::$mysqlAvailable;
    }

    public static function getUserPersistenceMapper(): UserPersistenceMapper
    {
        ClassLoader::loadClass('UserPersistenceMapper');

        return new UserPersistenceMapper();
    }

    public static function getGastoPersistenceMapper(): GastoPersistenceMapper
    {
        ClassLoader::loadClass('GastoPersistenceMapper');

        return new GastoPersistenceMapper();
    }

    public static function getUserRepository(): SaveUserPort&UpdateUserPort&GetUserByIdPort&GetUserByEmailPort&GetAllUsersPort&DeleteUserPort
    {
        if (self::shouldUseMySql()) {
            ClassLoader::loadClass('UserRepositoryMySQL');
            return new UserRepositoryMySQL(
                self::getPdo(),
                self::getUserPersistenceMapper()
            );
        }

        ClassLoader::loadClass('InMemoryUserRepository');
        if (self::$inMemoryUserRepository === null) {
            $storageFile = dirname(__DIR__) . '/storage/data/users.json';
            self::$inMemoryUserRepository = new InMemoryUserRepository(true, $storageFile);
        }

        return self::$inMemoryUserRepository;
    }

    public static function getGastoRepository(): SaveGastoPort&UpdateGastoPort&GetGastoByIdPort&GetAllGastosPort&DeleteGastoPort
    {
        if (self::shouldUseMySql()) {
            ClassLoader::loadClass('GastoRepositoryMySQL');
            return new GastoRepositoryMySQL(
                self::getPdo(),
                self::getGastoPersistenceMapper()
            );
        }

        ClassLoader::loadClass('InMemoryGastoRepository');
        if (self::$inMemoryGastoRepository === null) {
            $storageFile = dirname(__DIR__) . '/storage/data/gastos.json';
            self::$inMemoryGastoRepository = new InMemoryGastoRepository(true, $storageFile);
        }

        return self::$inMemoryGastoRepository;
    }

    public static function getCachePort(): CachePort
    {
        ClassLoader::loadClass('FileCacheAdapter');
        if (self::$cacheAdapter === null) {
            self::$cacheAdapter = new FileCacheAdapter();
        }

        return self::$cacheAdapter;
    }

    public static function getEventPublisherPort(): FileQueueEventPublisherAdapter
    {
        ClassLoader::loadClass('FileQueueEventPublisherAdapter');
        if (self::$eventPublisher === null) {
            self::$eventPublisher = new FileQueueEventPublisherAdapter();
        }

        return self::$eventPublisher;
    }

    public static function getTaxIndicatorRestPort(): TaxIndicatorRestPort
    {
        ClassLoader::loadClass('TaxIndicatorRestAdapter');

        return new TaxIndicatorRestAdapter(
            EnvLoader::get('TAX_API_URL', '')
        );
    }

    public static function getSmtpConfigRepository(): ?SmtpConfigRepositoryMySQL
    {
        if (!self::shouldUseMySql()) {
            return null;
        }

        ClassLoader::loadClass('SmtpConfigRepositoryMySQL');

        return new SmtpConfigRepositoryMySQL(self::getPdo());
    }

    /**
     * @return array{source: string, host: string, port: int, encryption: string, username: string, password: string, remitente: string, from_address: string, from_name: string}
     */
    public static function resolveEffectiveSmtpSettings(): array
    {
        $overrideDb = strtolower(EnvLoader::get('SMTP_OVERRIDE_DB', 'false')) === 'true';

        if (!$overrideDb) {
            $dbRepo = self::getSmtpConfigRepository();
            $dbConfig = $dbRepo !== null ? $dbRepo->getActiveConfig() : null;
            if ($dbConfig !== null) {
                return array(
                    'source' => 'database',
                    'host' => $dbConfig['host'],
                    'port' => $dbConfig['port'],
                    'encryption' => $dbConfig['port'] === 465 ? 'ssl' : EnvLoader::get('SMTP_ENCRYPTION', 'tls'),
                    'username' => $dbConfig['username'],
                    'password' => $dbConfig['password'],
                    'remitente' => $dbConfig['remitente'],
                    'from_address' => $dbConfig['from_address'],
                    'from_name' => $dbConfig['from_name'],
                );
            }
        }

        return array(
            'source' => 'env',
            'host' => EnvLoader::get('SMTP_HOST', 'smtp.gmail.com'),
            'port' => EnvLoader::getInt('SMTP_PORT', 587),
            'encryption' => EnvLoader::get('SMTP_ENCRYPTION', 'tls'),
            'username' => EnvLoader::get('SMTP_USERNAME', ''),
            'password' => EnvLoader::get('SMTP_PASSWORD', ''),
            'remitente' => EnvLoader::get('SMTP_FROM_NAME', 'CRUD Usuarios y Gastos'),
            'from_address' => EnvLoader::get('SMTP_FROM_ADDRESS', ''),
            'from_name' => EnvLoader::get('SMTP_FROM_NAME', 'CRUD Usuarios y Gastos'),
        );
    }

    public static function invalidateEmailSender(): void
    {
        self::$emailSender = null;
    }

    public static function getEmailSenderService(): EmailSenderService
    {
        ClassLoader::loadClass('EmailSenderService');
        if (self::$emailSender !== null) {
            return self::$emailSender;
        }

        $strict = strtolower(EnvLoader::get('SMTP_STRICT', 'false')) === 'true';
        $smtp = self::resolveEffectiveSmtpSettings();

        self::$emailSender = new EmailSenderService(
            host: $smtp['host'],
            username: $smtp['username'],
            password: $smtp['password'],
            fromAddress: $smtp['from_address'],
            fromName: $smtp['from_name'],
            encryption: $smtp['encryption'],
            port: $smtp['port'],
            outboxDir: null,
            strictMode: $strict,
            brevoApiKey: EnvLoader::get('BREVO_API_KEY', ''),
            resendApiKey: EnvLoader::get('RESEND_API_KEY', '')
        );

        return self::$emailSender;
    }

    public static function getEmailNotificationService(): EmailNotificationService
    {
        ClassLoader::loadClass('EmailNotificationService');
        $strict = strtolower(EnvLoader::get('SMTP_STRICT', 'false')) === 'true';
        $templatesDir = dirname(__DIR__) . '/Infrastructure/Entrypoints/Web/Presentation/Views/emails';

        return new EmailNotificationService(
            self::getEmailSenderService(),
            $strict,
            $templatesDir
        );
    }

    public static function getCreateUserUseCase(): CreateUserUseCase
    {
        ClassLoader::loadClass('CreateUserService');
        $repository = self::getUserRepository();

        return new CreateUserService(
            $repository,
            $repository,
            self::getEmailNotificationService()
        );
    }

    public static function getUpdateUserUseCase(): UpdateUserUseCase
    {
        ClassLoader::loadClass('UpdateUserService');
        $repository = self::getUserRepository();

        return new UpdateUserService(
            $repository,
            $repository,
            $repository,
            self::getEmailNotificationService()
        );
    }

    public static function getGetUserByIdUseCase(): GetUserByIdUseCase
    {
        ClassLoader::loadClass('GetUserByIdService');

        return new GetUserByIdService(
            self::getUserRepository()
        );
    }

    public static function getGetAllUsersUseCase(): GetAllUsersUseCase
    {
        ClassLoader::loadClass('GetAllUsersService');

        return new GetAllUsersService(
            self::getUserRepository()
        );
    }

    public static function getDeleteUserUseCase(): DeleteUserUseCase
    {
        ClassLoader::loadClass('DeleteUserService');
        $repository = self::getUserRepository();

        return new DeleteUserService(
            $repository,
            $repository
        );
    }

    public static function getCreateGastoUseCase(): CreateGastoUseCase
    {
        ClassLoader::loadClass('CreateGastoService');

        return new CreateGastoService(
            self::getGastoRepository(),
            self::getCachePort(),
            self::getEventPublisherPort()
        );
    }

    public static function getUpdateGastoUseCase(): UpdateGastoUseCase
    {
        ClassLoader::loadClass('UpdateGastoService');
        $repository = self::getGastoRepository();

        return new UpdateGastoService(
            $repository,
            $repository,
            self::getCachePort(),
            self::getEventPublisherPort()
        );
    }

    public static function getGetGastoByIdUseCase(): GetGastoByIdUseCase
    {
        ClassLoader::loadClass('GetGastoByIdService');

        return new GetGastoByIdService(
            self::getGastoRepository()
        );
    }

    public static function getGetAllGastosUseCase(): GetAllGastosUseCase
    {
        ClassLoader::loadClass('GetAllGastosService');

        return new GetAllGastosService(
            self::getGastoRepository(),
            self::getCachePort(),
            EnvLoader::getInt('CACHE_TTL_SECONDS', 120)
        );
    }

    public static function getDeleteGastoUseCase(): DeleteGastoUseCase
    {
        ClassLoader::loadClass('DeleteGastoService');
        $repository = self::getGastoRepository();

        return new DeleteGastoService(
            $repository,
            $repository,
            self::getCachePort(),
            self::getEventPublisherPort()
        );
    }

    public static function getUserWebMapper(): UserWebMapper
    {
        ClassLoader::loadClass('UserWebMapper');

        return new UserWebMapper();
    }

    public static function getGastoWebMapper(): GastoWebMapper
    {
        ClassLoader::loadClass('GastoWebMapper');

        return new GastoWebMapper();
    }

    public static function getUserController(): UserController
    {
        ClassLoader::loadClass('UserController');

        return new UserController(
            self::getCreateUserUseCase(),
            self::getUpdateUserUseCase(),
            self::getGetUserByIdUseCase(),
            self::getGetAllUsersUseCase(),
            self::getDeleteUserUseCase(),
            self::getUserWebMapper()
        );
    }

    public static function getGastoController(): GastoController
    {
        ClassLoader::loadClass('GastoController');

        return new GastoController(
            self::getCreateGastoUseCase(),
            self::getUpdateGastoUseCase(),
            self::getGetGastoByIdUseCase(),
            self::getGetAllGastosUseCase(),
            self::getDeleteGastoUseCase(),
            self::getGastoWebMapper()
        );
    }

    public static function getUserRestController(): UserRestController
    {
        ClassLoader::loadClass('UserRestController');

        return new UserRestController(
            self::getCreateUserUseCase(),
            self::getUpdateUserUseCase(),
            self::getGetUserByIdUseCase(),
            self::getGetAllUsersUseCase(),
            self::getDeleteUserUseCase(),
            self::getLoginUseCase()
        );
    }

    public static function getGastoRestController(): GastoRestController
    {
        ClassLoader::loadClass('GastoRestController');

        return new GastoRestController(
            self::getCreateGastoUseCase(),
            self::getUpdateGastoUseCase(),
            self::getGetGastoByIdUseCase(),
            self::getGetAllGastosUseCase(),
            self::getDeleteGastoUseCase()
        );
    }

    public static function getLoginUseCase(): LoginUseCase
    {
        ClassLoader::loadClass('LoginService');

        return new LoginService(
            self::getUserRepository()
        );
    }
}