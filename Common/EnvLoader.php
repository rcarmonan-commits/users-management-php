<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — UTILIDAD COMPARTIDA: EnvLoader (Cargador de Variables de Entorno)
 * ============================================================================
 * CAPA: Común (`Common/EnvLoader`) — Soporte Transversal
 * PATRÓN: Environment Configuration Loader
 * PRINCIPIO SOLID: SRP (Carga y parseo seguro de archivos .env)
 *
 * ¿QUÉ HACE?
 *   Carga las variables de entorno desde el archivo .env hacia $_ENV y getenv() respetando configuraciones preexistentes.
 *
 * ¿CÓMO LO HACE?
 *   Lee línea por línea, ignora comentarios (#) y parsea pares clave=valor.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Permite la configuración flexible en local (sin Docker o con Docker) y en la nube (Render, Alwaysdata).
 * ============================================================================
 */

/**
 * Carga variables de entorno desde un archivo .env.
 *
 * Las variables reales del entorno del servidor tienen prioridad:
 * si una clave ya existe en $_ENV o en getenv(), no se sobreescribe.
 * Esto permite usar variables de servidor en producción sin tocar el código.
 */
final class EnvLoader
{
    public static function load(string $filePath): void
    {
        if (!is_file($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Ignorar comentarios y líneas vacías
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Eliminar comillas simples o dobles envolventes: VAR="valor"
            if (preg_match('/^([\'"])(.*)\1$/s', $value, $matches)) {
                $value = $matches[2];
            }

            // Las variables reales del entorno tienen prioridad sobre el .env
            if (getenv($name) === false && !isset($_ENV[$name])) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }

    /**
     * Obtiene el valor de una variable de entorno como string.
     * Devuelve $default si la clave no existe.
     */
    public static function get(string $key, string $default = ''): string
    {
        $value = $_ENV[$key] ?? getenv($key);

        return ($value !== false && $value !== null && $value !== '')
            ? (string) $value
            : $default;
    }

    /**
     * Obtiene el valor de una variable de entorno como entero.
     */
    public static function getInt(string $key, int $default = 0): int
    {
        $value = self::get($key);

        return $value !== '' ? (int) $value : $default;
    }
}
