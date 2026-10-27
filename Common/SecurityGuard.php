<?php

declare(strict_types=1);

/**
 * ============================================================================
 * GUÍA DE SEGURIDAD ANTIVULNERACIONES (OWASP TOP 10) — SecurityGuard
 * ============================================================================
 * CAPA: Transversal de Infraestructura/Composición (`Common/SecurityGuard.php`)
 * PATRÓN: Defense-in-Depth (Defensa en Profundidad) sin contaminar el Dominio
 *
 * ¿QUÉ HACE ESTE COMPONENTE?
 *   Centraliza todos los mecanismos de endurecimiento HTTP, sesiones, tokens
 *   criptográficos anti-CSRF, limitación de tasa anti-fuerza bruta (*Rate Limiting*),
 *   mitigación de enumeración por tiempos (*Timing Attack*) y sanitización de cabeceras.
 *
 * ¿QUÉ VULNERABILIDADES OWASP BLOQUEA Y CÓMO LO HACE?
 *   1. OWASP A01 (Broken Access Control / CSRF):
 *      - Genera un token criptográfico de 256 bits (`random_bytes(32)`) por sesión.
 *      - Valida cada petición `POST` en tiempo constante con `hash_equals()`.
 *      - Inyecta automáticamente el campo oculto `_csrf_token` en todo `<form method="POST">`.
 *   2. OWASP A02 (Cryptographic & Sensitive Data Failures):
 *      - Filtra campos sensibles (`password`) antes de guardar datos en `Flash::setOld()`.
 *      - Configura cookies de sesión con `HttpOnly`, `SameSite=Lax` y `Secure` bajo HTTPS.
 *   3. OWASP A03 (Header / CRLF Injection):
 *      - `sanitizeHeaderValue()` elimina `\r`, `\n` y `\0` en redirecciones HTTP.
 *   4. OWASP A05 (Security Misconfiguration / Clickjacking / XSS):
 *      - Emite `Content-Security-Policy`, `X-Frame-Options: SAMEORIGIN`,
 *        `X-Content-Type-Options: nosniff`, `Referrer-Policy` y `Permissions-Policy`.
 *   5. OWASP A07 & API2:2023 (Identification, Session & Stateless API JWT Auth):
 *      - `session.use_strict_mode = 1` + `session_regenerate_id(true)` tras login
 *        (bloquea ataques de *Session Fixation*).
 *      - Autenticación obligatoria en la API REST mediante **JWT RFC 7519 firmado con
 *        `HS256` (`HMAC-SHA256`)** en `Authorization: Bearer <token>`:
 *          * Rechaza peticiones anónimas sin token (`HTTP 401` + `WWW-Authenticate`).
 *          * Bloquea ataques `alg: "none"`, tokens expirados (`exp`) y firmas adulteradas
 *            usando comparación criptográfica en tiempo constante (`hash_equals()`).
 *          * Control de autorización RBAC y Anti-BOLA/IDOR (`HTTP 403 Forbidden`).
 *      - *Sliding-Window Rate Limiter* contra fuerza bruta en login, recuperación y API.
 *      - `mitigateTimingEnumeration()` iguala el costo CPU de BCrypt cuando un correo
 *        no existe para impedir enumeración de usuarios por diferencia de milisegundos.
 * ============================================================================
 */
final class SecurityGuard
{
    private static ?string $rateLimitDir = null;
    private static ?string $dummyBcryptHash = null;
    private static ?string $jwtSecret = null;

    /**
     * Permite redirigir el almacén de Rate Limiting a un directorio aislado durante pruebas.
     */
    public static function setRateLimitDirectory(?string $directory): void
    {
        self::$rateLimitDir = $directory;
    }

    /**
     * Permite fijar o reiniciar el secreto HMAC-SHA256 de firma JWT (útil en pruebas aisladas).
     */
    public static function setJwtSecret(?string $secret): void
    {
        self::$jwtSecret = $secret;
    }

    /**
     * Inicia la sesión PHP aplicando banderas estrictas de seguridad en la cookie.
     */
    public static function startHardenedSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (!headers_sent()) {
            $isHttps = self::isHttpsRequest();
            @ini_set('session.use_strict_mode', '1');
            @ini_set('session.use_only_cookies', '1');
            @ini_set('session.cookie_httponly', '1');

            session_set_cookie_params(array(
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ));

            session_start();
            return;
        }

        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = array();
        }
    }

    /**
     * Regenera el identificador de sesión tras autenticarse (Anti-Session Fixation).
     */
    public static function regenerateSessionOnLogin(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }
    }

    /**
     * Destruye completamente la sesión activa e invalida la cookie en el navegador.
     */
    public static function destroySessionCompletely(): void
    {
        $_SESSION = array();

        if (!headers_sent() && ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                array(
                    'expires' => time() - 42000,
                    'path' => $params['path'] ?: '/',
                    'domain' => $params['domain'] ?: '',
                    'secure' => (bool) $params['secure'],
                    'httponly' => true,
                    'samesite' => 'Lax',
                )
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /**
     * Construye la directiva Content-Security-Policy adaptada para vistas Web o Swagger UI.
     */
    public static function buildContentSecurityPolicy(bool $allowSwaggerCdn = false): string
    {
        $scriptSrc = $allowSwaggerCdn
            ? "'self' 'unsafe-inline' https://unpkg.com"
            : "'self' 'unsafe-inline'";
        $styleSrc = $allowSwaggerCdn
            ? "'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com"
            : "'self' 'unsafe-inline' https://fonts.googleapis.com";

        return implode('; ', array(
            "default-src 'self'",
            "script-src {$scriptSrc}",
            "style-src {$styleSrc}",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: https:",
            "connect-src 'self' https://unpkg.com",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ));
    }

    /**
     * Emite las cabeceras HTTP defensivas recomendadas por OWASP.
     */
    public static function applyHttpSecurityHeaders(bool $allowSwaggerCdn = false): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header('X-XSS-Protection: 0');
        header('Content-Security-Policy: ' . self::buildContentSecurityPolicy($allowSwaggerCdn));
    }

    /**
     * Obtiene o genera el token anti-CSRF de 256 bits asociado a la sesión actual.
     */
    public static function csrfToken(): string
    {
        self::startHardenedSession();

        if (empty($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    /**
     * Retorna el elemento HTML `<input type="hidden" name="_csrf_token" ...>` listo para formularios.
     */
    public static function csrfField(): string
    {
        $safe = htmlspecialchars(self::csrfToken(), ENT_QUOTES, 'UTF-8');

        return '<input type="hidden" name="_csrf_token" value="' . $safe . '">';
    }

    /**
     * Verifica el token CSRF recibido usando comparación en tiempo constante (`hash_equals`).
     */
    public static function verifyCsrfToken(?string $submittedToken): bool
    {
        self::startHardenedSession();

        if (!is_string($submittedToken) || $submittedToken === '') {
            return false;
        }

        $expected = $_SESSION['_csrf_token'] ?? '';
        if (!is_string($expected) || $expected === '') {
            return false;
        }

        return hash_equals($expected, $submittedToken);
    }

    /**
     * Garantiza que todo `<form method="POST">...</form>` renderizado por `View::render()`
     * incluya su token sincronizador `_csrf_token` sin duplicarlo si ya fue declarado.
     */
    public static function injectCsrfIntoPostForms(string $html): string
    {
        $field = self::csrfField();

        $result = preg_replace_callback(
            '/(<form\b[^>]*\bmethod\s*=\s*["\']?post["\']?[^>]*>)(.*?<\/form>)/is',
            static function (array $matches) use ($field): string {
                if (stripos($matches[2], 'name="_csrf_token"') !== false || stripos($matches[2], "name='_csrf_token'") !== false) {
                    return $matches[0];
                }

                return $matches[1] . "\n    " . $field . $matches[2];
            },
            $html
        );

        return is_string($result) ? $result : $html;
    }

    /**
     * Verifica si el cliente aún se encuentra dentro del umbral permitido de intentos
     * en la ventana deslizante `$windowSeconds` (devuelve `true` si se permite continuar).
     */
    public static function checkRateLimit(
        string $bucket,
        string $clientKey,
        int $maxAttempts = 5,
        int $windowSeconds = 300
    ): bool {
        $timestamps = self::readWindowAttempts($bucket, $clientKey, $windowSeconds);

        return count($timestamps) < $maxAttempts;
    }

    /**
     * Registra un intento fallido o sensible dentro de la ventana deslizante.
     */
    public static function recordFailedAttempt(
        string $bucket,
        string $clientKey,
        int $windowSeconds = 300
    ): void {
        $timestamps = self::readWindowAttempts($bucket, $clientKey, $windowSeconds);
        $timestamps[] = time();
        self::writeWindowAttempts($bucket, $clientKey, $timestamps);
    }

    /**
     * Limpia el contador de intentos fallidos tras una autenticación exitosa.
     */
    public static function clearAttempts(string $bucket, string $clientKey): void
    {
        $file = self::resolveRateLimitFile($bucket, $clientKey);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    /**
     * Ejecuta un `password_verify` ficticio cuando el correo consultado no existe
     * para neutralizar ataques de enumeración de cuentas por diferencia de tiempo.
     */
    public static function mitigateTimingEnumeration(string $plainPassword = 'dummy'): void
    {
        if (self::$dummyBcryptHash === null) {
            self::$dummyBcryptHash = password_hash('TimingEqualizerDummyKey2026!', PASSWORD_BCRYPT);
        }

        password_verify($plainPassword, self::$dummyBcryptHash);
    }

    /**
     * Elimina secuencias CRLF y bytes nulos para impedir HTTP Response Splitting / Header Injection.
     */
    public static function sanitizeHeaderValue(string $value): string
    {
        return str_replace(array("\r", "\n", "\0"), '', $value);
    }

    /**
     * Elimina contraseñas y tokens antes de persistir datos antiguos de formulario en sesión.
     *
     * @param array<string, string> $input
     * @return array<string, string>
     */
    public static function sanitizeOldInput(array $input): array
    {
        unset($input['password'], $input['clave'], $input['password_confirmation'], $input['_csrf_token']);

        return $input;
    }

    /**
     * Obtiene la dirección IP del cliente compatible con proxies inversos Cloud (Render).
     */
    public static function clientIp(): string
    {
        $forwarded = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
        if ($forwarded !== '') {
            $parts = explode(',', $forwarded);
            $candidate = trim($parts[0]);
            if (filter_var($candidate, FILTER_VALIDATE_IP) !== false) {
                return $candidate;
            }
        }

        $remote = trim((string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));

        return filter_var($remote, FILTER_VALIDATE_IP) !== false ? $remote : '127.0.0.1';
    }

    /**
     * ========================================================================
     * AUTENTICACIÓN STATELESS JWT (RFC 7519 HS256) Y AUTORIZACIÓN RBAC DE API
     * ========================================================================
     * Emite un JSON Web Token firmado con HMAC-SHA256 (`HS256`) tras validar
     * credenciales en `POST /api/auth/login`.
     *
     * @return array{token: string, expires_in: int, expires_at: string}
     */
    public static function issueApiJwt(
        string $userId,
        string $email,
        string $name,
        string $role,
        ?int $ttlSeconds = null
    ): array {
        $configuredTtl = (int) (getenv('JWT_TTL_SECONDS') ?: ($_ENV['JWT_TTL_SECONDS'] ?? 3600));
        $effectiveTtl = $ttlSeconds !== null ? $ttlSeconds : max(60, $configuredTtl);

        $now = time();
        $exp = $now + $effectiveTtl;

        $header = array(
            'alg' => 'HS256',
            'typ' => 'JWT',
        );

        $payload = array(
            'iss' => 'users-gastos-hexagonal-api',
            'sub' => trim($userId),
            'email' => strtolower(trim($email)),
            'name' => trim($name),
            'role' => strtoupper(trim($role)),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $exp,
            'jti' => bin2hex(random_bytes(16)),
        );

        $headerB64 = self::base64UrlEncode((string) json_encode($header, JSON_UNESCAPED_SLASHES));
        $payloadB64 = self::base64UrlEncode((string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $signingInput = $headerB64 . '.' . $payloadB64;

        $rawSignature = hash_hmac('sha256', $signingInput, self::resolveJwtSecret(), true);
        $signatureB64 = self::base64UrlEncode($rawSignature);

        return array(
            'token' => $signingInput . '.' . $signatureB64,
            'expires_in' => max(0, $effectiveTtl),
            'expires_at' => gmdate('Y-m-d\TH:i:s\Z', $exp),
        );
    }

    /**
     * Verifica criptográficamente un token `Bearer <JWT>` recibido en la API REST:
     *   1. Estructura exacta de 3 segmentos Base64URL (`header.payload.signature`).
     *   2. Bloqueo explícito del ataque `alg: "none"` exigiendo `alg === "HS256"` y `typ === "JWT"`.
     *   3. Comparación en tiempo constante `hash_equals()` sobre la firma `HMAC-SHA256`.
     *   4. Validación de emisor (`iss`), sujeto (`sub`), rol (`role`), `nbf` y expiración (`exp`).
     *
     * @return array<string, mixed> Claims verificados del token
     */
    public static function verifyApiJwt(string $jwt): array
    {
        $trimmed = trim($jwt);
        if ($trimmed === '' || strlen($trimmed) > 4096) {
            throw new RuntimeException('Acceso no autorizado: token Bearer ausente o con formato inválido.', 401);
        }

        $parts = explode('.', $trimmed);
        if (count($parts) !== 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
            throw new RuntimeException('Acceso no autorizado: estructura de token JWT malformada.', 401);
        }

        $headerRaw = self::base64UrlDecode($parts[0]);
        $payloadRaw = self::base64UrlDecode($parts[1]);
        $providedSignature = self::base64UrlDecode($parts[2]);

        if ($headerRaw === null || $payloadRaw === null || $providedSignature === null) {
            throw new RuntimeException('Acceso no autorizado: codificación Base64URL inválida en token JWT.', 401);
        }

        $header = json_decode($headerRaw, true, 8);
        $payload = json_decode($payloadRaw, true, 8);

        if (!is_array($header) || !is_array($payload)) {
            throw new RuntimeException('Acceso no autorizado: encabezados o claims JSON corruptos en token JWT.', 401);
        }

        // Defensa crítica anti-vulneración `alg: "none"` y confusión de algoritmo
        if (($header['alg'] ?? '') !== 'HS256' || ($header['typ'] ?? '') !== 'JWT') {
            throw new RuntimeException('Acceso no autorizado: algoritmo de firma JWT no permitido.', 401);
        }

        $expectedSignature = hash_hmac('sha256', $parts[0] . '.' . $parts[1], self::resolveJwtSecret(), true);
        if (!hash_equals($expectedSignature, $providedSignature)) {
            throw new RuntimeException('Acceso no autorizado: firma criptográfica del token JWT inválida o adulterada.', 401);
        }

        if (($payload['iss'] ?? '') !== 'users-gastos-hexagonal-api') {
            throw new RuntimeException('Acceso no autorizado: emisor (iss) del token JWT no reconocido.', 401);
        }

        $sub = isset($payload['sub']) && is_string($payload['sub']) ? trim($payload['sub']) : '';
        $role = isset($payload['role']) && is_string($payload['role']) ? strtoupper(trim($payload['role'])) : '';
        if ($sub === '' || $role === '') {
            throw new RuntimeException('Acceso no autorizado: claims de identidad incompletos en el token JWT.', 401);
        }

        $now = time();
        if (!isset($payload['nbf']) || !is_numeric($payload['nbf']) || ((int) $payload['nbf']) > ($now + 5)) {
            throw new RuntimeException('Acceso no autorizado: el token JWT aún no es válido (nbf).', 401);
        }

        if (!isset($payload['exp']) || !is_numeric($payload['exp']) || ((int) $payload['exp']) <= $now) {
            throw new RuntimeException('Acceso no autorizado: el token JWT ha expirado. Autentícate nuevamente en POST /api/auth/login.', 401);
        }

        $payload['sub'] = $sub;
        $payload['role'] = $role;

        return $payload;
    }

    /**
     * Extrae el token de `Authorization: Bearer <token>` compatible con Apache (`mod_rewrite` / CGI),
     * Nginx/FastCGI, PHP built-in server y proxies Cloud como Render.
     *
     * @param array<string, mixed>|null $server
     */
    public static function extractBearerToken(?array $server = null): ?string
    {
        $srv = $server ?? $_SERVER;
        $rawHeader = '';

        foreach (array('HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION', 'Authorization') as $key) {
            if (!empty($srv[$key]) && is_string($srv[$key])) {
                $rawHeader = trim($srv[$key]);
                break;
            }
        }

        if ($rawHeader === '' && $server === null && function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                foreach ($headers as $hName => $hVal) {
                    if (is_string($hName) && strtolower($hName) === 'authorization' && is_string($hVal)) {
                        $rawHeader = trim($hVal);
                        break;
                    }
                }
            }
        }

        if ($rawHeader === '') {
            return null;
        }

        if (preg_match('/^Bearer\s+([^\r\n\0\s]+)$/i', $rawHeader, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Impone Control de Acceso Basado en Roles (OWASP API5:2023 Broken Function Level Authorization).
     *
     * @param string[] $allowedRoles
     */
    public static function assertApiRole(
        string $currentRole,
        array $allowedRoles,
        string $actionDescription = 'ejecutar esta operación'
    ): void {
        $normalized = strtoupper(trim($currentRole));
        if (!in_array($normalized, $allowedRoles, true)) {
            throw new RuntimeException(
                sprintf('Acceso prohibido (RBAC): el rol %s no está autorizado para %s.', $normalized, $actionDescription),
                403
            );
        }
    }

    /**
     * Previene ataques BOLA / IDOR (OWASP API1:2023 Broken Object Level Authorization):
     * únicamente un `ADMIN` o el titular exacto de la cuenta puede modificar ese recurso.
     */
    public static function assertApiSelfOrAdmin(
        string $currentUserId,
        string $currentRole,
        string $targetUserId
    ): void {
        if (strtoupper(trim($currentRole)) !== 'ADMIN' && $currentUserId !== $targetUserId) {
            throw new RuntimeException(
                'Acceso prohibido (Anti-BOLA/IDOR): solo un Administrador puede modificar perfiles de terceros.',
                403
            );
        }
    }

    /**
     * Impide que un Administrador elimine accidental o maliciosamente su propia cuenta activa.
     */
    public static function assertNotSelfDeletion(string $currentUserId, string $targetUserId): void
    {
        if ($currentUserId !== '' && $currentUserId === $targetUserId) {
            throw new RuntimeException(
                'Operación bloqueada por seguridad: no puedes eliminar tu propia cuenta con sesión/token activo.',
                403
            );
        }
    }

    private static function resolveJwtSecret(): string
    {
        if (self::$jwtSecret !== null && self::$jwtSecret !== '') {
            return self::$jwtSecret;
        }

        $envSecret = trim((string) (getenv('JWT_SECRET') ?: ($_ENV['JWT_SECRET'] ?? '')));
        if (strlen($envSecret) >= 16) {
            self::$jwtSecret = $envSecret;
            return self::$jwtSecret;
        }

        $cacheBase = dirname(__DIR__) . '/storage/cache';
        if (!is_dir($cacheBase)) {
            @mkdir($cacheBase, 0775, true);
        }

        $keyFile = $cacheBase . '/jwt_secret.key';
        if (is_file($keyFile)) {
            $stored = trim((string) @file_get_contents($keyFile));
            if (strlen($stored) >= 32) {
                self::$jwtSecret = $stored;
                return self::$jwtSecret;
            }
        }

        $generated = bin2hex(random_bytes(32));
        @file_put_contents($keyFile, $generated, LOCK_EX);
        @chmod($keyFile, 0600);
        self::$jwtSecret = $generated;

        return self::$jwtSecret;
    }

    private static function base64UrlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $encoded): ?string
    {
        if (preg_match('/^[A-Za-z0-9\-_]+$/', $encoded) !== 1) {
            return null;
        }

        $remainder = strlen($encoded) % 4;
        if ($remainder === 1) {
            return null;
        }
        if ($remainder > 0) {
            $encoded .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);

        return is_string($decoded) ? $decoded : null;
    }

    private static function isHttpsRequest(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }

    private static function resolveRateLimitDir(): string
    {
        $dir = self::$rateLimitDir ?? (dirname(__DIR__) . '/storage/cache/ratelimit');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir;
    }

    private static function resolveRateLimitFile(string $bucket, string $clientKey): string
    {
        $normalized = strtolower(trim($bucket)) . '|' . strtolower(trim($clientKey));

        return self::resolveRateLimitDir() . '/rl_' . sha1($normalized) . '.json';
    }

    /**
     * @return int[]
     */
    private static function readWindowAttempts(string $bucket, string $clientKey, int $windowSeconds): array
    {
        $file = self::resolveRateLimitFile($bucket, $clientKey);
        if (!is_file($file)) {
            return array();
        }

        $raw = (string) @file_get_contents($file);
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return array();
        }

        $cutoff = time() - max(1, $windowSeconds);
        $valid = array();
        foreach ($decoded as $ts) {
            $intTs = (int) $ts;
            if ($intTs >= $cutoff) {
                $valid[] = $intTs;
            }
        }

        return $valid;
    }

    /**
     * @param int[] $timestamps
     */
    private static function writeWindowAttempts(string $bucket, string $clientKey, array $timestamps): void
    {
        $file = self::resolveRateLimitFile($bucket, $clientKey);
        @file_put_contents($file, json_encode(array_values($timestamps)), LOCK_EX);
    }

    /**
     * Registra que un usuario tiene una clave de recuperación temporal activa pendiente por cambiar.
     * Persiste de forma resiliente tanto en archivo cache local como en la tabla MySQL `hex_temporary_password_flags`.
     */
    public static function flagTemporaryPassword(string $userId, string $email): void
    {
        $cleanId = trim($userId);
        if ($cleanId === '') {
            return;
        }

        $cacheDir = dirname(__DIR__) . '/storage/cache/pwd_flags';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }
        $flagFile = $cacheDir . '/temp_' . sha1($cleanId) . '.json';
        @file_put_contents($flagFile, json_encode(array(
            'user_id' => $cleanId,
            'email' => strtolower(trim($email)),
            'created_at' => time(),
        )), LOCK_EX);

        try {
            if (class_exists('DependencyInjection')) {
                $pdo = DependencyInjection::getPdo();
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS hex_temporary_password_flags (
                        user_id VARCHAR(64) PRIMARY KEY,
                        email VARCHAR(150) NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                $stmt = $pdo->prepare("
                    INSERT INTO hex_temporary_password_flags (user_id, email)
                    VALUES (:user_id, :email)
                    ON DUPLICATE KEY UPDATE email = VALUES(email), created_at = CURRENT_TIMESTAMP
                ");
                $stmt->execute(array(
                    ':user_id' => $cleanId,
                    ':email' => strtolower(trim($email)),
                ));
            }
        } catch (Throwable) {
            // Degrada limpiamente al almacenamiento en cache
        }
    }

    /**
     * Verifica si el usuario inició sesión utilizando una clave temporal y tiene pendiente definir su clave permanente.
     */
    public static function isTemporaryPasswordActive(string $userId): bool
    {
        $cleanId = trim($userId);
        if ($cleanId === '') {
            return false;
        }

        $cacheDir = dirname(__DIR__) . '/storage/cache/pwd_flags';
        $flagFile = $cacheDir . '/temp_' . sha1($cleanId) . '.json';
        if (is_file($flagFile)) {
            return true;
        }

        try {
            if (class_exists('DependencyInjection')) {
                $pdo = DependencyInjection::getPdo();
                $check = $pdo->query("SHOW TABLES LIKE 'hex_temporary_password_flags'");
                if ($check !== false && $check->fetchColumn() !== false) {
                    $stmt = $pdo->prepare("SELECT user_id FROM hex_temporary_password_flags WHERE user_id = :id LIMIT 1");
                    $stmt->execute(array(':id' => $cleanId));
                    if ($stmt->fetchColumn() !== false) {
                        return true;
                    }
                }
            }
        } catch (Throwable) {
            return false;
        }

        return false;
    }

    /**
     * Limpia la marca de clave temporal una vez el usuario definió exitosamente su nueva contraseña.
     */
    public static function clearTemporaryPasswordFlag(string $userId): void
    {
        $cleanId = trim($userId);
        if ($cleanId === '') {
            return;
        }

        $cacheDir = dirname(__DIR__) . '/storage/cache/pwd_flags';
        $flagFile = $cacheDir . '/temp_' . sha1($cleanId) . '.json';
        if (is_file($flagFile)) {
            @unlink($flagFile);
        }

        try {
            if (class_exists('DependencyInjection')) {
                $pdo = DependencyInjection::getPdo();
                $check = $pdo->query("SHOW TABLES LIKE 'hex_temporary_password_flags'");
                if ($check !== false && $check->fetchColumn() !== false) {
                    $stmt = $pdo->prepare("DELETE FROM hex_temporary_password_flags WHERE user_id = :id");
                    $stmt->execute(array(':id' => $cleanId));
                }
            }
        } catch (Throwable) {
            // Ignorar
        }
    }
}