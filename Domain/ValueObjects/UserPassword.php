<?php


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — VALUE OBJECT DDD: UserPassword
 * ============================================================================
 * CAPA: Dominio (`Domain/ValueObjects`)
 * PATRÓN: Value Object (DDD) + Seguridad Criptográfica OWASP
 * PRINCIPIO SOLID: SRP (Gestión segura de contraseñas y hashing BCrypt)
 *
 * ¿QUÉ HACE?
 *   Encapsula la contraseña del usuario, distinguiendo entre contraseñas en texto plano y hashes seguros ya computados.
 *
 * ¿CÓMO LO HACE?
 *   Ofrece fromPlainText() que valida longitud mínima y genera hash con password_hash(..., PASSWORD_BCRYPT), y fromHash() para hidratar desde la persistencia.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Impide que contraseñas en texto plano se almacenen o viajen accidentalmente por la arquitectura.
 * ============================================================================
 */

require_once __DIR__ . '/../Exceptions/InvalidUserPasswordException.php';

class UserPassword
{
    private $value;

    public function __construct($value)
    {
        $normalizedValue = trim((string) $value);

        if ($normalizedValue === '') {
            throw InvalidUserPasswordException::becauseValueIsEmpty();
        }

        if (strlen($normalizedValue) < 8) {
            throw InvalidUserPasswordException::becauseLengthIsTooShort(8);
        }

        $this->value = $normalizedValue;
    }

    /**
     * Creates a UserPassword from plain text, validating and hashing it with bcrypt.
     * Use this when a user creates or changes their password.
     */
    public static function fromPlainText(string $raw): self
    {
        $normalizedValue = trim($raw);

        if ($normalizedValue === '') {
            throw InvalidUserPasswordException::becauseValueIsEmpty();
        }

        if (strlen($normalizedValue) < 8) {
            throw InvalidUserPasswordException::becauseLengthIsTooShort(8);
        }

        // BCrypt hash is 60+ characters; passes the constructor min-8 check.
        $hash = password_hash($normalizedValue, PASSWORD_BCRYPT);

        return new self($hash);
    }

    /**
     * Creates a UserPassword from an already-hashed value loaded from storage.
     * No validation of min length is needed (hash is always > 8 chars).
     */
    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }

    /**
     * Verifies a plain-text password against the stored bcrypt hash.
     */
    public function verifyPlain(string $plain): bool
    {
        return password_verify(trim($plain), $this->value);
    }

    public function value()
    {
        return $this->value;
    }

    public function equals(UserPassword $other)
    {
        return $this->value === $other->value();
    }

    public function __toString()
    {
        return $this->value;
    }
}

