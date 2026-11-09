<?php

declare(strict_types=1);

final class ApiResponse
{
    private int $statusCode;
    /** @var array<string, mixed> */
    private array $payload;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(int $statusCode, array $payload)
    {
        $this->statusCode = $statusCode;
        $this->payload = $payload;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function success(array $data, int $statusCode = 200, string $message = 'OK'): self
    {
        return new self($statusCode, array(
            'success' => true,
            'message' => $message,
            'data' => $data,
        ));
    }

    public static function error(string $errorCode, string $message, int $statusCode): self
    {
        return new self($statusCode, array(
            'success' => false,
            'error' => array(
                'code' => $errorCode,
                'message' => $message,
            ),
        ));
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);
            header('Content-Type: application/json; charset=UTF-8');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
        }

        echo json_encode($this->payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}