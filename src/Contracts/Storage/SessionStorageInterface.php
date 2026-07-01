<?php

declare(strict_types=1);

namespace AndyDefer\StorageKit\Contracts\Storage;

/**
 * Extended storage interface for session-based storage.
 *
 * Provides session-specific methods like namespace management
 * and session status checking.
 */
interface SessionStorageInterface extends StorageInterface
{
    /**
     * Returns the current namespace used for session data isolation.
     *
     * @return string The namespace
     */
    public function getNamespace(): string;

    /**
     * Changes the namespace for session data isolation.
     *
     * @param  string  $namespace  New namespace
     */
    public function setNamespace(string $namespace): void;

    /**
     * Checks if the session is active and available.
     *
     * @return bool True if session is active, false otherwise
     */
    public function isSessionActive(): bool;

    /**
     * Gets all data from the current namespace.
     *
     * @return array<string, mixed> All session data in this namespace
     */
    public function getAll(): array;

    /**
     * Checks if the session storage is empty in the current namespace.
     *
     * @return bool True if empty, false otherwise
     */
    public function isEmpty(): bool;
}
