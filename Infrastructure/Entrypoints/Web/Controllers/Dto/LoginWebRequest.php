<?php

declare(strict_types=1);

final class LoginWebRequest
{
    private string $email;
    private string $password;

    public function __construct(string $email, string $password)
    {
        $this->email = trim(strtolower($email));
        $this->password = $password;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}
