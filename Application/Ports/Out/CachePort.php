<?php

declare(strict_types=1);

interface CachePort
{
    public function get(string $key): mixed;

    public function set(string $key, mixed $value, int $ttlSeconds = 120): void;

    public function forget(string $key): void;

    public function flushByPrefix(string $prefix): void;
}