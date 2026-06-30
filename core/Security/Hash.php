<?php

declare(strict_types=1);

namespace Kadosys\Core\Security;

/**
 * Hash
 *
 * Encapsula as funcoes nativas de hashing de senha do PHP (bcrypt),
 * centralizando a logica para que toda a plataforma utilize o mesmo
 * algoritmo e configuracao de custo.
 */
final class Hash
{
    private const ALGO = PASSWORD_BCRYPT;

    private const OPTIONS = ['cost' => 12];

    public static function make(string $value): string
    {
        return password_hash($value, self::ALGO, self::OPTIONS);
    }

    public static function check(string $value, string $hash): bool
    {
        return password_verify($value, $hash);
    }

    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, self::ALGO, self::OPTIONS);
    }
}
