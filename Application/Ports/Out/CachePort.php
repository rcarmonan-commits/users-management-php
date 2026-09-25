<?php

declare(strict_types=1);

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — PUERTO DE INFRAESTRUCTURA DE CACHÉ: CachePort
 * ============================================================================
 * CAPA: Aplicación (`Application/Ports/Out`)
 *
 * ¿QUÉ HACE?
 *   Abstrae el almacenamiento temporal de lecturas frecuentes (CQRS Queries) y su
 *   invalidación selectiva por prefijo cuando ocurre un Command de escritura.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Permite usar `FileCacheAdapter` localmente y sustituirlo mañana por Redis o
 *   Memcached sin alterar `GetAllGastosService` ni `CreateGastoService`.
 * ============================================================================
 */
interface CachePort
{
    public function get(string $key): mixed;

    public function set(string $key, mixed $value, int $ttlSeconds = 120): void;

    public function forget(string $key): void;

    public function flushByPrefix(string $prefix): void;
}