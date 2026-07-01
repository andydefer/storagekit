<?php

declare(strict_types=1);

namespace AndyDefer\StorageKit\Storage;

use AndyDefer\StorageKit\Contracts\Storage\SessionStorageInterface;

/**
 * Session-based storage using PHP's $_SESSION superglobal.
 *
 * Data is stored in the user's session and persists across requests
 * for the same user session. Requires session_start() to be called
 * before usage.
 *
 * @example
 * session_start();
 * $storage = new SessionStorage('app_data');
 * $storage->set('user_id', 123);
 * $userId = $storage->get('user_id');
 */
final class SessionStorage implements SessionStorageInterface
{
    private string $namespace;

    public function __construct(string $namespace = 'storage_kit')
    {
        $this->namespace = $namespace;
        $this->ensureSessionStarted();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $data = $this->getSessionData();

        return $data[$key] ?? $default;
    }

    public function getMultiple(array $keys): array
    {
        $data = $this->getSessionData();
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $data[$key] ?? null;
        }

        return $result;
    }

    public function set(string $key, mixed $value): void
    {
        $data = $this->getSessionData();
        $data[$key] = $value;
        $this->saveSessionData($data);
    }

    public function setMultiple(array $items): void
    {
        $data = $this->getSessionData();

        foreach ($items as $key => $value) {
            $data[$key] = $value;
        }

        $this->saveSessionData($data);
    }

    public function delete(string $key): bool
    {
        $data = $this->getSessionData();

        if (! array_key_exists($key, $data)) {
            return false;
        }

        unset($data[$key]);
        $this->saveSessionData($data);

        return true;
    }

    public function deleteMultiple(array $keys): void
    {
        $data = $this->getSessionData();

        foreach ($keys as $key) {
            unset($data[$key]);
        }

        $this->saveSessionData($data);
    }

    public function exists(string $key): bool
    {
        $data = $this->getSessionData();

        return array_key_exists($key, $data);
    }

    public function clear(): void
    {
        $_SESSION[$this->namespace] = [];
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function setNamespace(string $namespace): void
    {
        // Récupérer les données actuelles
        $data = $this->getSessionData();

        // Supprimer l'ancien namespace
        unset($_SESSION[$this->namespace]);

        // Définir le nouveau namespace
        $this->namespace = $namespace;

        // Restaurer les données dans le nouveau namespace
        $_SESSION[$this->namespace] = $data;
    }

    public function isSessionActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function getAll(): array
    {
        return $this->getSessionData();
    }

    public function isEmpty(): bool
    {
        return empty($this->getSessionData());
    }

    /**
     * Ensures that the session has been started.
     *
     * @throws \RuntimeException If session cannot be started
     */
    private function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            throw new \RuntimeException(
                'Session must be started before using SessionStorage. Call session_start() first.'
            );
        }
    }

    /**
     * Retrieves session data for the namespace.
     *
     * @return array<string, mixed>
     */
    private function getSessionData(): array
    {
        return $_SESSION[$this->namespace] ?? [];
    }

    /**
     * Saves session data for the namespace.
     *
     * @param  array<string, mixed>  $data
     */
    private function saveSessionData(array $data): void
    {
        $_SESSION[$this->namespace] = $data;
    }
}
