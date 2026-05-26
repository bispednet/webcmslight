<?php
declare(strict_types=1);

namespace App\Services\Auth;

final class Base58
{
    private const ALPHABET = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

    public static function decode(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $bytes = [0];
        $alphabetLength = strlen(self::ALPHABET);

        for ($i = 0, $length = strlen($value); $i < $length; $i++) {
            $carry = strpos(self::ALPHABET, $value[$i]);
            if ($carry === false) {
                return null;
            }

            for ($j = 0, $size = count($bytes); $j < $size; $j++) {
                $carry += $bytes[$j] * $alphabetLength;
                $bytes[$j] = $carry & 0xff;
                $carry >>= 8;
            }

            while ($carry > 0) {
                $bytes[] = $carry & 0xff;
                $carry >>= 8;
            }
        }

        for ($i = 0, $length = strlen($value); $i < $length && $value[$i] === '1'; $i++) {
            $bytes[] = 0;
        }

        return implode('', array_map('chr', array_reverse($bytes)));
    }
}
