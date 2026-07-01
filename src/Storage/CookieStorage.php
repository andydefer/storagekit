<?php

declare(strict_types=1);

namespace AndyDefer\StorageKit\Storage;

use AndyDefer\StorageKit\Contracts\Storage\CookieStorageInterface;

/**
 * Cookie-based storage using PHP's setcookie() function.
 *
 * Data is stored in the user's browser cookies and persists across
 * requests. Data is limited by cookie size (4KB) and number of cookies.
 *
 * @example
 * $storage = new CookieStorage('app_data');
 * $storage->set('user_id', 123);
 * $userId = $storage->get('user_id');
 *
 * @see https://www.php.net/manual/en/function.setcookie.php
 */
final class CookieStorage implements CookieStorageInterface
{
    private string $prefix;

    private ?int $expires = null;

    private ?string $domain = null;

    private string $path = '/';

    private bool $secure = false;

    private bool $httpOnly = true;

    private ?string $sameSite = 'Lax';

    public function __construct(
        string $prefix = 'storage_',
        ?int $expires = null,
        ?string $domain = null,
        string $path = '/',
        bool $secure = false,
        bool $httpOnly = true,
        ?string $sameSite = 'Lax'
    ) {
        $this->prefix = $prefix;
        $this->expires = $expires ?? (time() + 86400 * 30); // 30 days by default
        $this->domain = $domain;
        $this->path = $path;
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;
        $this->sameSite = $sameSite;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $cookieKey = $this->getCookieKey($key);

        if (! isset($_COOKIE[$cookieKey])) {
            return $default;
        }

        $value = $_COOKIE[$cookieKey];

        return $this->decodeValue($value);
    }

    public function getMultiple(array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }

        return $result;
    }

    public function set(string $key, mixed $value): void
    {
        $cookieKey = $this->getCookieKey($key);
        $encodedValue = $this->encodeValue($value);

        $this->setCookie($cookieKey, $encodedValue);
        $_COOKIE[$cookieKey] = $encodedValue;
    }

    public function setMultiple(array $items): void
    {
        foreach ($items as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function delete(string $key): bool
    {
        $cookieKey = $this->getCookieKey($key);

        if (! isset($_COOKIE[$cookieKey])) {
            return false;
        }

        unset($_COOKIE[$cookieKey]);
        $this->setCookie($cookieKey, '', time() - 3600);

        return true;
    }

    public function deleteMultiple(array $keys): void
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
    }

    public function exists(string $key): bool
    {
        $cookieKey = $this->getCookieKey($key);

        return isset($_COOKIE[$cookieKey]);
    }

    public function clear(): void
    {
        $prefix = $this->prefix;

        foreach ($_COOKIE as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                unset($_COOKIE[$key]);
                $this->setCookie($key, '', time() - 3600);
            }
        }
    }

    public function setExpires(int|string $expires): self
    {
        $this->expires = is_string($expires) ? strtotime($expires) : $expires;

        return $this;
    }

    public function getExpires(): ?int
    {
        return $this->expires;
    }

    public function setDomain(string $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setSecure(bool $secure = true): self
    {
        $this->secure = $secure;

        return $this;
    }

    public function isSecure(): bool
    {
        return $this->secure;
    }

    public function setHttpOnly(bool $httpOnly = true): self
    {
        $this->httpOnly = $httpOnly;

        return $this;
    }

    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    public function setSameSite(string $sameSite): self
    {
        if (! in_array($sameSite, ['Lax', 'Strict', 'None'], true)) {
            throw new \InvalidArgumentException(
                'SameSite must be one of: Lax, Strict, None'
            );
        }

        $this->sameSite = $sameSite;

        return $this;
    }

    public function getSameSite(): ?string
    {
        return $this->sameSite;
    }

    public function getAll(): array
    {
        $result = [];
        $prefix = $this->prefix;

        foreach ($_COOKIE as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $originalKey = substr($key, strlen($prefix));
                $result[$originalKey] = $this->decodeValue($value);
            }
        }

        return $result;
    }

    public function isEmpty(): bool
    {
        $prefix = $this->prefix;

        foreach ($_COOKIE as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                return false;
            }
        }

        return true;
    }

    public function setMultipleWithConfig(array $items): void
    {
        foreach ($items as $key => $value) {
            $cookieKey = $this->getCookieKey($key);
            $encodedValue = $this->encodeValue($value);

            $this->setCookie($cookieKey, $encodedValue);
            $_COOKIE[$cookieKey] = $encodedValue;
        }
    }

    /**
     * Builds the full cookie key with prefix.
     *
     * @param  string  $key  Original key
     * @return string Full cookie key
     */
    private function getCookieKey(string $key): string
    {
        return $this->prefix.$key;
    }

    /**
     * Encodes a value for cookie storage.
     *
     * @param  mixed  $value  Value to encode
     * @return string Encoded value
     */
    private function encodeValue(mixed $value): string
    {
        return base64_encode(serialize($value));
    }

    /**
     * Decodes a value from cookie storage.
     *
     * @param  string  $value  Encoded value
     * @return mixed Decoded value
     */
    private function decodeValue(string $value): mixed
    {
        $decoded = base64_decode($value, true);

        if ($decoded === false) {
            return $value;
        }

        $unserialized = @unserialize($decoded);

        return $unserialized !== false ? $unserialized : $value;
    }

    /**
     * Sets a cookie with the current configuration.
     *
     * @param  string  $key  Cookie key
     * @param  string  $value  Cookie value
     * @param  int|null  $expires  Custom expiration (optional)
     */
    private function setCookie(string $key, string $value, ?int $expires = null): void
    {
        $expires = $expires ?? $this->expires;

        $result = setcookie(
            $key,
            $value,
            [
                'expires' => $expires,
                'path' => $this->path,
                'domain' => $this->domain,
                'secure' => $this->secure,
                'httponly' => $this->httpOnly,
                'samesite' => $this->sameSite,
            ]
        );

        if (! $result) {
            throw new \RuntimeException("Failed to set cookie: {$key}");
        }
    }
}
