<?php

namespace App\Support;

class ProdusenValueParser
{
    public static function tokens(mixed $value): array
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return [];
        }

        $tokens = [];
        foreach (explode(';', $text) as $part) {
            $part = trim($part);
            if (preg_match('/^(\d+)\s*,\s*(.+)$/u', $part, $matches)) {
                $tokens[] = $matches[1];
                $tokens[] = trim($matches[2]);
                continue;
            }

            $tokens[] = $part;
        }

        return $tokens;
    }

    public static function resolve(mixed $value, array $producersById, array $idsByName): array
    {
        $tokens = self::tokens($value);
        $ids = [];
        $invalid = [];

        foreach ($tokens as $token) {
            if ($token === '') {
                $invalid[] = '(kosong)';
                continue;
            }

            if (ctype_digit($token)) {
                $id = (int) $token;
                if (isset($producersById[$id])) {
                    $ids[] = $id;
                } else {
                    $invalid[] = $token;
                }

                continue;
            }

            $matchingIds = $idsByName[$token] ?? [];
            if (count($matchingIds) === 1) {
                $ids[] = (int) $matchingIds[0];
            } else {
                $invalid[] = $token;
            }
        }

        return [
            'ids' => array_values(array_unique($ids)),
            'invalid' => array_values(array_unique($invalid)),
        ];
    }
}
