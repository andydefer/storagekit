<?php

namespace AndyDefer\StorageKit\Contracts\Storage;

use AndyDefer\StorageKit\Records\CacheStorageStatsRecord;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;

/**
 * Interface pour CacheStorage avec support TTL et statistiques
 */
interface CacheStorageInterface extends StorageInterface
{
    /**
     * Stocke une valeur avec une durée de vie (TTL)
     *
     * @param  string  $key  Clé d'identification
     * @param  mixed  $value  Valeur à stocker
     * @param  int  $ttl  Durée de vie en secondes
     */
    public function setWithTTL(string $key, mixed $value, int $ttl): void;

    /**
     * Modifie la durée de vie d'une clé existante
     *
     * @param  string  $key  Clé d'identification
     * @param  int  $seconds  Nouvelle durée de vie en secondes
     */
    public function setTTL(string $key, int $seconds): void;

    /**
     * Récupère les statistiques du cache
     *
     * @return CacheStorageStatsRecord Statistiques
     */
    public function getStats(): CacheStorageStatsRecord;

    /**
     * Récupère l'instance du driver PhpFastCache sous-jacent
     *
     * @return ExtendedCacheItemPoolInterface Driver PhpFastCache
     */
    public function getDriver(): ExtendedCacheItemPoolInterface;

    /**
     * Récupère le nom du driver utilisé
     *
     * @return string Nom du driver (Files, Sqlite, etc.)
     */
    public function getDriverName(): string;

    /**
     * Récupère le préfixe des clés
     *
     * @return string Préfixe des clés
     */
    public function getCacheKeyPrefix(): string;

    /**
     * Définit le préfixe des clés
     *
     * @param  string  $prefix  Nouveau préfixe
     */
    public function setCacheKeyPrefix(string $prefix): void;

    /**
     * Vider tout le cache
     */
    public function clear(): void;
}
