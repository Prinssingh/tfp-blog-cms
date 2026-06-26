<?php

declare(strict_types=1);

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use stdClass;

class JwtHelper
{
    private static string $algo = 'HS256';

    public static function encode(array $payload, int $ttl): string
    {
        $now = time();

        $claims = array_merge($payload, [
            'iat' => $now,
            'exp' => $now + $ttl,
        ]);

        return JWT::encode($claims, self::secret(), self::$algo);
    }

    public static function decode(string $token): stdClass
    {
        return JWT::decode($token, new Key(self::secret(), self::$algo));
    }

    public static function accessToken(array $payload): string
    {
        $ttl = (int) ($_ENV['JWT_ACCESS_TTL'] ?? 3600);
        return self::encode($payload, $ttl);
    }

    public static function refreshToken(array $payload): string
    {
        $ttl = (int) ($_ENV['JWT_REFRESH_TTL'] ?? 604800);
        return self::encode(array_merge($payload, ['type' => 'refresh']), $ttl);
    }

    private static function secret(): string
    {
        return $_ENV['JWT_SECRET'];
    }
}
