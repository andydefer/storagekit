<?php

namespace AndyDefer\StorageKit\Contracts\Storage;

use AndyDefer\PhpJsonl\JsonlService;
use AndyDefer\StorageKit\Records\JsonlStorageStatsRecord;

/**
 * Interface pour JsonlStorage avec support TTL, batch et statistiques
 */
interface JsonlStorageInterface extends StorageInterface
{
    /**
     * Sauvegarde l'état complet d'une structure avec contexte
     *
     * @param  string  $key  Clé d'identification
     * @param  array  $state  État à sauvegarder
     * @param  string|null  $context  Contexte (optionnel)
     */
    public function saveState(string $key, array $state, ?string $context = null): void;

    /**
     * Récupère l'état complet d'une structure avec contexte
     *
     * @param  string  $key  Clé d'identification
     * @param  string|null  $context  Contexte (optionnel)
     * @return array|null État récupéré ou null si inexistant
     */
    public function loadState(string $key, ?string $context = null): ?array;

    /**
     * Définit la durée de vie globale des données
     *
     * @param  int  $seconds  Durée de vie en secondes (0 = pas d'expiration)
     */
    public function setTTL(int $seconds): void;

    /**
     * Récupère la durée de vie globale des données
     *
     * @return int Durée de vie en secondes
     */
    public function getTTL(): int;

    /**
     * Nettoie les entrées expirées
     *
     * @return int Nombre d'entrées supprimées
     */
    public function cleanExpired(): int;

    /**
     * Récupère les statistiques du storage
     *
     * @return JsonlStorageStatsRecord Statistiques
     */
    public function getStats(): JsonlStorageStatsRecord;

    /**
     * Récupère le service JSONL sous-jacent
     *
     * @return JsonlService Service JSONL
     */
    public function getJsonlService(): JsonlService;
}
