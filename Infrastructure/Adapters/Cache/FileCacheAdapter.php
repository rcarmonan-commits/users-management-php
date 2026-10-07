<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../Application/Ports/Out/CachePort.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — ADAPTADOR DE CACHÉ CON EXPIRACIÓN TTL: FileCacheAdapter
 * ============================================================================
 * CAPA: Infraestructura — Adaptadores (`Infrastructure/Adapters/Cache`)
 * PATRÓN: Cache-Aside + Seguridad contra *Path Traversal* (OWASP A01)
 *
 * ¿QUÉ HACE?
 *   Almacena resultados serializados en JSON con tiempo de vida (`expires_at`) y
 *   permite invalidar entradas por prefijo (`gastos:`) tras cada operación de escritura.
 *
 * ¿CÓMO EVITA PATH TRAVERSAL?
 *   Nunca usa la llave directamente como nombre de archivo: deriva un hash `sha1($key)`
 *   hexadecimal fijo de 40 caracteres (`resolveFile`), haciendo imposible escapar del
 *   directorio de caché con secuencias `../`.
 * ============================================================================
 */
final class FileCacheAdapter implements CachePort
{
    private string $cacheDirectory;

    public function __construct(?string $cacheDirectory = null)
    {
        $this->cacheDirectory = $cacheDirectory ?? dirname(__DIR__, 3) . '/storage/cache';
        if (!is_dir($this->cacheDirectory)) {
            @mkdir($this->cacheDirectory, 0775, true);
        }
    }

    public function get(string $key): mixed
    {
        $file = $this->resolveFile($key);
        if (!is_file($file)) {
            return null;
        }

        $raw = (string) file_get_contents($file);
        $entry = json_decode($raw, true);

        if (!is_array($entry) || !isset($entry['expires_at'])) {
            @unlink($file);
            return null;
        }

        if ((int) $entry['expires_at'] < time()) {
            @unlink($file);
            return null;
        }

        return $entry['payload'] ?? null;
    }

    public function set(string $key, mixed $value, int $ttlSeconds = 120): void
    {
        if (!is_dir($this->cacheDirectory)) {
            @mkdir($this->cacheDirectory, 0775, true);
        }

        $entry = array(
            'key' => $key,
            'expires_at' => time() + max(1, $ttlSeconds),
            'payload' => $value,
        );

        @file_put_contents(
            $this->resolveFile($key),
            json_encode($entry, JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    public function forget(string $key): void
    {
        $file = $this->resolveFile($key);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    public function flushByPrefix(string $prefix): void
    {
        if (!is_dir($this->cacheDirectory)) {
            return;
        }

        $files = glob($this->cacheDirectory . '/*.cache.json');
        if (!is_array($files)) {
            return;
        }

        foreach ($files as $file) {
            $raw = (string) @file_get_contents($file);
            $entry = json_decode($raw, true);
            if (!is_array($entry) || str_starts_with((string) ($entry['key'] ?? ''), $prefix)) {
                @unlink($file);
            }
        }
    }

    private function resolveFile(string $key): string
    {
        return $this->cacheDirectory . '/' . sha1($key) . '.cache.json';
    }
}