<?php

declare(strict_types=1);

final class ApiRequest
{
    private string $method;
    private string $path;
    /** @var array<string, mixed> */
    private array $queryParams;
    /** @var array<string, mixed> */
    private array $body;

    /**
     * @param array<string, mixed> $queryParams
     * @param array<string, mixed> $body
     */
    public function __construct(
        string $method,
        string $path,
        array $queryParams = array(),
        array $body = array()
    ) {
        $this->method = strtoupper(trim($method));
        $this->path = $path;
        $this->queryParams = $queryParams;
        $this->body = $body;
    }

    public static function fromGlobals(string $resolvedPath): self
    {
        $rawInput = (string) file_get_contents('php://input');
        $decoded = array();

        if ($rawInput !== '') {
            $parsed = json_decode($rawInput, true);
            if (!is_array($parsed)) {
                throw new InvalidArgumentException('El cuerpo de la petición debe ser JSON válido.');
            }
            $decoded = $parsed;
        } elseif (!empty($_POST)) {
            $decoded = $_POST;
        }

        return new self(
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $resolvedPath,
            $_GET,
            $decoded
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $key, ?string $default = null): ?string
    {
        if (!isset($this->queryParams[$key])) {
            return $default;
        }

        return trim((string) $this->queryParams[$key]);
    }

    /**
     * @return array<string, mixed>
     */
    public function body(): array
    {
        return $this->body;
    }

    public function stringField(string $key, string $default = ''): string
    {
        return isset($this->body[$key]) ? trim((string) $this->body[$key]) : $default;
    }
}