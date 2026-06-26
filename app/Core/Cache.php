<?php

declare(strict_types=1);

namespace App\Core;

class Cache
{
    private static string $dir = '';

    private static function dir(): string
    {
        if (self::$dir === '') {
            self::$dir = defined('BASE_PATH')
                ? BASE_PATH . '/storage/cache/data'
                : sys_get_temp_dir() . '/tfp_cache';
        }
        return self::$dir;
    }

    private static function path(string $key): string
    {
        $hash = md5($key);
        $sub  = self::dir() . '/' . substr($hash, 0, 2);

        if (!is_dir($sub)) {
            mkdir($sub, 0775, true);
        }

        return $sub . '/' . $hash . '.cache';
    }

    private static function tagIndexPath(string $tag): string
    {
        $dir = self::dir() . '/tags';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return $dir . '/' . md5($tag) . '.tag';
    }

    public static function get(string $key): mixed
    {
        $path = self::path($key);

        if (!file_exists($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if ($raw === false) {
            return null;
        }

        $data = @unserialize($raw);

        if (!is_array($data) || !isset($data['expires'], $data['value'])) {
            return null;
        }

        if ($data['expires'] !== 0 && $data['expires'] < time()) {
            @unlink($path);
            return null;
        }

        return $data['value'];
    }

    public static function set(string $key, mixed $value, int $ttl = 3600, array $tags = []): void
    {
        $path    = self::path($key);
        $expires = $ttl === 0 ? 0 : time() + $ttl;

        file_put_contents($path, serialize([
            'expires' => $expires,
            'value'   => $value,
        ]), LOCK_EX);

        foreach ($tags as $tag) {
            self::registerKeyForTag($tag, $key);
        }
    }

    public static function forget(string $key): void
    {
        $path = self::path($key);
        if (file_exists($path)) {
            @unlink($path);
        }
    }

    public static function flushTag(string $tag): void
    {
        $indexPath = self::tagIndexPath($tag);

        if (!file_exists($indexPath)) {
            return;
        }

        $keys = @unserialize(file_get_contents($indexPath));

        if (is_array($keys)) {
            foreach ($keys as $key) {
                self::forget($key);
            }
        }

        @unlink($indexPath);
    }

    public static function remember(string $key, int $ttl, callable $callback, array $tags = []): mixed
    {
        $value = self::get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        self::set($key, $value, $ttl, $tags);
        return $value;
    }

    private static function registerKeyForTag(string $tag, string $key): void
    {
        $indexPath = self::tagIndexPath($tag);
        $keys      = [];

        if (file_exists($indexPath)) {
            $existing = @unserialize(file_get_contents($indexPath));
            if (is_array($existing)) {
                $keys = $existing;
            }
        }

        $keys[$key] = $key;
        file_put_contents($indexPath, serialize($keys), LOCK_EX);
    }
}
