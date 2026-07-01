<?php

namespace AndyDefer\StorageKit\Contracts\Storage;

interface StorageInterface
{
    /**
     * Récupère une valeur
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Stocke une valeur
     */
    public function set(string $key, mixed $value): void;

    /**
     * Supprime une valeur
     */
    public function delete(string $key): bool;

    /**
     * Vérifie si une valeur existe
     */
    public function exists(string $key): bool;

    /**
     * Vide tout le storage
     */
    public function clear(): void;

    /**
     * Récupère plusieurs valeurs en lot
     *
     * @param  string[]  $keys
     * @return array<string, mixed>
     */
    public function getMultiple(array $keys): array;

    /**
     * Stocke plusieurs valeurs en lot
     *
     * @param  array<string, mixed>  $items
     */
    public function setMultiple(array $items): void;

    /**
     * Supprime plusieurs valeurs en lot
     *
     * @param  string[]  $keys
     */
    public function deleteMultiple(array $keys): void;
}
