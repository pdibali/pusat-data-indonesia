<?php

namespace App\Support;

class Setting
{
    protected static string $path = 'app/settings.json';

    public static function all(): array
    {
        $file = storage_path(self::$path);

        if (!file_exists($file)) {
            file_put_contents($file, json_encode(['midtrans_enabled' => true]));
        }

        return json_decode(file_get_contents($file), true) ?? [];
    }

    public static function get(string $key, $default = null)
    {
        return self::all()[$key] ?? $default;
    }

    public static function set(string $key, $value): void
    {
        $data = self::all();
        $data[$key] = $value;

        file_put_contents(storage_path(self::$path), json_encode($data, JSON_PRETTY_PRINT));
    }
}