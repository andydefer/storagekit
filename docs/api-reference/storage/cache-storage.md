# CacheStorage - Référence Technique

## Description

CacheStorage est une implémentation de stockage utilisant PhpFastCache comme backend. Elle supporte plusieurs drivers (Files, Sqlite) avec gestion du TTL et des statistiques de performance.

## Hiérarchie / Implémentations

```
CacheStorageInterface
    └── CacheStorage
```

**Interfaces implémentées :** `StorageInterface`, `CacheStorageInterface`

## Rôle principal

CacheStorage fournit un stockage haute performance avec support du TTL (Time-To-Live) et des statistiques. Elle s'appuie sur PhpFastCache pour offrir plusieurs backends (fichiers, SQLite) et est idéale pour les applications nécessitant un cache persistant avec expiration automatique.

## Installation

```bash
composer require andydefer/storage-kit
```

## API / Méthodes publiques

### `__construct(CacheDriver $driver = CacheDriver::FILES, ?CacheConfigRecord $config = null, string $cacheKeyPrefix = 'storage_'): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$driver` | `CacheDriver` | Driver de cache (Files ou Sqlite) |
| `$config` | `CacheConfigRecord|null` | Configuration du cache |
| `$cacheKeyPrefix` | `string` | Préfixe des clés de cache |

**Retourne :** `void`

**Exemple :**
```php
$storage = new CacheStorage(CacheDriver::FILES);
$storage = new CacheStorage(CacheDriver::SQLITE, new CacheConfigRecord('/path/to/cache.sqlite'));
```

---

### `get(string $key, mixed $default = null): mixed`

Récupère une valeur par sa clé.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé d'identification |
| `$default` | `mixed` | Valeur par défaut si la clé n'existe pas |

**Retourne :** `mixed` - La valeur stockée ou la valeur par défaut

**Exemple :**
```php
$user = $storage->get('user_123', ['name' => 'Unknown']);
```

---

### `getMultiple(array $keys): array`

Récupère plusieurs valeurs en une seule opération.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$keys` | `string[]` | Liste des clés à récupérer |

**Retourne :** `array<string, mixed>` - Tableau associatif clé → valeur

**Exemple :**
```php
$users = $storage->getMultiple(['user_123', 'user_456']);
```

---

### `set(string $key, mixed $value): void`

Stocke une valeur avec une clé donnée.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé d'identification |
| `$value` | `mixed` | Valeur à stocker |

**Retourne :** `void`

**Exemple :**
```php
$storage->set('user_123', ['name' => 'John', 'age' => 30]);
```

---

### `setMultiple(array $items): void`

Stocke plusieurs valeurs en une seule opération.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$items` | `array<string, mixed>` | Tableau associatif clé → valeur |

**Retourne :** `void`

**Exemple :**
```php
$storage->setMultiple([
    'user_123' => ['name' => 'John'],
    'user_456' => ['name' => 'Jane'],
]);
```

---

### `setWithTTL(string $key, mixed $value, int $ttl): void`

Stocke une valeur avec une durée de vie spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé d'identification |
| `$value` | `mixed` | Valeur à stocker |
| `$ttl` | `int` | Durée de vie en secondes |

**Retourne :** `void`

**Exemple :**
```php
$storage->setWithTTL('session_123', 'active', 3600); // Expire dans 1h
```

---

### `delete(string $key): bool`

Supprime une valeur par sa clé.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé à supprimer |

**Retourne :** `bool` - `true` si la clé existait et a été supprimée, `false` sinon

**Exemple :**
```php
$deleted = $storage->delete('user_123'); // true
```

---

### `deleteMultiple(array $keys): void`

Supprime plusieurs clés en une seule opération.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$keys` | `string[]` | Liste des clés à supprimer |

**Retourne :** `void`

**Exemple :**
```php
$storage->deleteMultiple(['user_123', 'user_456']);
```

---

### `exists(string $key): bool`

Vérifie si une clé existe et n'est pas expirée.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé à vérifier |

**Retourne :** `bool` - `true` si la clé existe et est valide, `false` sinon

**Exemple :**
```php
if ($storage->exists('user_123')) {
    $user = $storage->get('user_123');
}
```

---

### `setTTL(string $key, int $seconds): void`

Modifie la durée de vie d'une clé existante.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé à modifier |
| `$seconds` | `int` | Nouvelle durée de vie en secondes |

**Retourne :** `void`

**Exemple :**
```php
$storage->setTTL('session_123', 1800); // Prolonge de 30 min
```

---

### `clear(): void`

Supprime toutes les données du cache.

**Retourne :** `void`

**Exemple :**
```php
$storage->clear(); // Vide tout le cache
```

---

### `getStats(): CacheStorageStatsRecord`

Récupère les statistiques du cache.

**Retourne :** `CacheStorageStatsRecord` - Statistiques du cache

**Exemple :**
```php
$stats = $storage->getStats();
echo "Hits: " . $stats->hits;
echo "Misses: " . $stats->misses;
```

---

### `getDriver(): ExtendedCacheItemPoolInterface`

Récupère l'instance du driver PhpFastCache.

**Retourne :** `ExtendedCacheItemPoolInterface` - Driver PhpFastCache

**Exemple :**
```php
$driver = $storage->getDriver();
```

---

### `getDriverName(): string`

Récupère le nom du driver utilisé.

**Retourne :** `string` - Nom du driver (Files, Sqlite)

**Exemple :**
```php
$name = $storage->getDriverName(); // 'Files'
```

---

### `getCacheKeyPrefix(): string`

Récupère le préfixe des clés.

**Retourne :** `string` - Préfixe des clés

**Exemple :**
```php
$prefix = $storage->getCacheKeyPrefix(); // 'storage_'
```

---

### `setCacheKeyPrefix(string $prefix): void`

Définit le préfixe des clés pour toutes les opérations.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$prefix` | `string` | Nouveau préfixe |

**Retourne :** `void`

**Exemple :**
```php
$storage->setCacheKeyPrefix('app_');
```

## Cas d'utilisation

### Cas 1 : Cache de sessions utilisateur

```php
class SessionCache
{
    private CacheStorage $storage;
    
    public function __construct(CacheStorage $storage)
    {
        $this->storage = $storage;
    }
    
    public function startSession(string $sessionId, array $data, int $ttl = 3600): void
    {
        $this->storage->setWithTTL('session_' . $sessionId, $data, $ttl);
    }
    
    public function getSession(string $sessionId): ?array
    {
        return $this->storage->get('session_' . $sessionId);
    }
    
    public function extendSession(string $sessionId, int $extraTime = 1800): void
    {
        if ($this->storage->exists('session_' . $sessionId)) {
            $this->storage->setTTL('session_' . $sessionId, $extraTime);
        }
    }
}
```

### Cas 2 : Cache de résultats d'API avec statistiques

```php
class ApiCache
{
    private CacheStorage $storage;
    
    public function __construct(CacheStorage $storage)
    {
        $this->storage = $storage;
    }
    
    public function get(string $endpoint, int $ttl = 300): ?array
    {
        $key = 'api_' . md5($endpoint);
        
        if ($this->storage->exists($key)) {
            return $this->storage->get($key);
        }
        
        $data = $this->fetchFromApi($endpoint);
        $this->storage->setWithTTL($key, $data, $ttl);
        
        return $data;
    }
    
    public function getStats(): array
    {
        $stats = $this->storage->getStats();
        return [
            'hit_rate' => $stats->hits / ($stats->hits + $stats->misses) * 100,
            'items' => $stats->items_count,
        ];
    }
}
```

### Cas 3 : Cache avec différents drivers

```php
class CacheManager
{
    private CacheStorage $cache;
    
    public function __construct(string $driver = 'files')
    {
        $driver = $driver === 'sqlite' 
            ? CacheDriver::SQLITE 
            : CacheDriver::FILES;
            
        $this->cache = new CacheStorage($driver);
    }
    
    public function get(string $key): mixed
    {
        return $this->cache->get($key);
    }
    
    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        $this->cache->setWithTTL($key, $value, $ttl);
    }
}
```

## Flux d'exécution

```
get($key)
    ↓
buildCacheKey($key) → Ajoute le préfixe
    ↓
cache->getItem($cacheKey) → Récupère l'item PhpFastCache
    ↓
item->isHit() && !item->isExpired()
    ├── Oui → stats['hits']++, return item->get()
    └── Non → stats['misses']++, return $default
```

```
setWithTTL($key, $value, $ttl)
    ↓
buildCacheKey($key) → Ajoute le préfixe
    ↓
cache->getItem($cacheKey) → Récupère l'item
    ↓
item->set($value)->expiresAfter($ttl)
    ↓
cache->save($item)
    ↓
stats['sets']++
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Driver non supporté | `PhpfastcacheInvalidConfigurationException` | `Configuration option "path" is not supported` |
| Statistiques non disponibles | - | Ignoré silencieusement |

## Intégration

### Avec StorageFactory

```php
use AndyDefer\StorageKit\Factory\StorageFactory;
use AndyDefer\StorageKit\Enums\StorageSystem;

$factory = new StorageFactory();
$storage = $factory->create(StorageSystem::CACHE);
```

### Avec les structures AlgoKIT

```php
use AndyDefer\AlgoKIT\Algorithms\Trie;
use AndyDefer\StorageKit\Storage\CacheStorage;

$storage = new CacheStorage();
$trie = new Trie($storage, 'autocomplete');
$trie->insert('laravel');
```

## Performance

| Opération | Complexité | Notes |
|-----------|------------|-------|
| `get()` | O(1) | Accès direct au cache |
| `set()` | O(1) | Écriture directe |
| `setWithTTL()` | O(1) | Écriture avec expiration |
| `exists()` | O(1) | Vérification directe |
| `getStats()` | O(1) | Statistiques agrégées |

**Statistiques :** Les hits/misses sont trackés en mémoire.

## Compatibilité

| Version | Support |
|---------|---------|
| PHP 8.1+ | ✅ Complet |
| PHP 8.0 | ✅ Complet |
| PHP 7.4 | ❌ Non (nécessite PHP 8.0+) |

## Exemple complet

```php
<?php

declare(strict_types=1);

use AndyDefer\StorageKit\Storage\CacheStorage;
use AndyDefer\StorageKit\Enums\CacheDriver;
use AndyDefer\StorageKit\Records\CacheConfigRecord;

// 1. Création avec Files driver
$storage = new CacheStorage(CacheDriver::FILES);

// 2. Stockage avec TTL
$storage->setWithTTL('user_123', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
], 3600);

// 3. Stockage multiple
$storage->setMultiple([
    'config' => ['debug' => true],
    'settings' => ['theme' => 'dark'],
]);

// 4. Récupération
$user = $storage->get('user_123');
$config = $storage->get('config');

// 5. Modification du TTL
$storage->setTTL('user_123', 7200); // Prolonge à 2h

// 6. Vérification d'existence
if ($storage->exists('user_123')) {
    echo "User exists";
}

// 7. Statistiques
$stats = $storage->getStats();
echo "Hits: {$stats->hits}, Misses: {$stats->misses}";

// 8. Driver SQLite
$config = new CacheConfigRecord('/tmp/cache.sqlite');
$sqliteStorage = new CacheStorage(CacheDriver::SQLITE, $config);

// 9. Nettoyage
$storage->delete('config');
$storage->deleteMultiple(['user_123', 'settings']);
$storage->clear();
```

## Voir aussi

- [`cookie-storage`](cookie-storage.md) - Stockage cookie
- [`jsonl-storage`](jsonl-storage.md) - Stockage jsonl
- [`memory-storage`](memory-storage.md) - Stockage mémoire
- [`session-storage`](session-storage.md) - Stockage session
- [`sqlite-storage`](sqlite-storage.md) - Stockage sqlite