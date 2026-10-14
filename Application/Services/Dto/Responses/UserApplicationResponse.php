<?php

declare(strict_types=1);

final class UserApplicationResponse
{
    private string $id;
    private string $name;
    private string $email;
    private string $role;
    private string $status;

    public function __construct(
        string $id,
        string $name,
        string $email,
        string $role,
        string $status
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->role = $role;
        $this->status = $status;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
        );
    }
}