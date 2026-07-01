<?php

declare(strict_types=1);

namespace AndyDefer\StorageKit\Contracts\Storage;

/**
 * Extended storage interface for cookie-based storage.
 *
 * Provides cookie-specific methods like expiration, domain, path,
 * secure/httponly flags management, and prefix handling.
 */
interface CookieStorageInterface extends StorageInterface
{
    /**
     * Returns the cookie prefix.
     *
     * @return string The cookie prefix
     */
    public function getPrefix(): string;

    /**
     * Sets the cookie expiration time.
     *
     * @param  int|string  $expires  Unix timestamp or relative time (e.g., '+1 hour')
     */
    public function setExpires(int|string $expires): self;

    /**
     * Returns the cookie expiration time.
     *
     * @return int|null Unix timestamp or null if not set
     */
    public function getExpires(): ?int;

    /**
     * Sets the cookie domain.
     *
     * @param  string  $domain  Domain name
     */
    public function setDomain(string $domain): self;

    /**
     * Returns the cookie domain.
     *
     * @return string|null Domain name or null if not set
     */
    public function getDomain(): ?string;

    /**
     * Sets the cookie path.
     *
     * @param  string  $path  Path on the server
     */
    public function setPath(string $path): self;

    /**
     * Returns the cookie path.
     *
     * @return string Path
     */
    public function getPath(): string;

    /**
     * Sets the secure flag for the cookie.
     *
     * @param  bool  $secure  True to send only over HTTPS
     */
    public function setSecure(bool $secure = true): self;

    /**
     * Returns whether the cookie is secure-only.
     */
    public function isSecure(): bool;

    /**
     * Sets the HttpOnly flag for the cookie.
     *
     * @param  bool  $httpOnly  True to make cookie inaccessible to JavaScript
     */
    public function setHttpOnly(bool $httpOnly = true): self;

    /**
     * Returns whether the cookie is HttpOnly.
     */
    public function isHttpOnly(): bool;

    /**
     * Sets the SameSite attribute for the cookie.
     *
     * @param  string  $sameSite  Lax|Strict|None
     */
    public function setSameSite(string $sameSite): self;

    /**
     * Returns the SameSite attribute.
     */
    public function getSameSite(): ?string;

    /**
     * Gets all cookie data.
     *
     * @return array<string, mixed>
     */
    public function getAll(): array;

    /**
     * Checks if the cookie storage is empty.
     */
    public function isEmpty(): bool;

    /**
     * Sets multiple cookies with the same configuration.
     *
     * @param  array<string, mixed>  $items
     */
    public function setMultipleWithConfig(array $items): void;
}
