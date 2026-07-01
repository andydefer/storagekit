# StorageFactory - Référence Technique

## Description

StorageFactory est une fabrique qui crée des instances de stockage selon le système choisi (Memory, JSONL, Cache). Elle centralise la configuration et la création des différents storages.

## Hiérarchie / Implémentations

```
StorageFactoryInterface
    └── StorageFactory
```

**Interfaces implémentées :** `StorageFactoryInterface`

## Rôle principal

StorageFactory simplifie la création des différents types de stockage en encapsulant la logique de configuration. Elle permet de changer facilement de système de stockage sans modifier le code client, et centralise les paramètres de configuration (chemin de base, TTL, niveaux de hachage).

## Installation

```bash
composer require andydefer/storage-kit
```

## API / Méthodes publiques

### `__construct(string $basePath = '/tmp/storage', int $ttl = 86400, int $hashLevels = 2)`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$basePath` | `string` | Chemin de base pour les storages persistants |
| `$ttl` | `int` | Durée de vie globale en secondes (défaut: 86400) |
| `$hashLevels` | `int` | Niveaux de hachage pour JSONL (défaut: 2) |

**Retourne :** `void`

**Exemple :**
```php
$factory = new StorageFactory('/var/data', 3600, 2);
```

---

### `create(StorageSystem $system): StorageInterface`

Crée une instance de storage selon le système choisi.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$system` | `StorageSystem` | Système de stockage à créer |

**Retourne :** `StorageInterface` - Instance du storage

**Exemple :**
```php
$storage = $factory->create(StorageSystem::JSONL);
```

---

### `createMemoryStorage(): MemoryStorage`

Crée une instance de MemoryStorage.

**Retourne :** `MemoryStorage` - Storage en mémoire

**Exemple :**
```php
$storage = $factory->createMemoryStorage();
```

---

### `createJsonlStorage(): JsonlStorage`

Crée une instance de JsonlStorage avec la configuration actuelle.

**Retourne :** `JsonlStorage` - Storage JSONL persistant

**Exemple :**
```php
$storage = $factory->createJsonlStorage();
```

---

### `createCacheStorage(CacheDriver $driver = CacheDriver::FILES, ?CacheConfigRecord $config = null, string $cacheKeyPrefix = 'storage_'): CacheStorage`

Crée une instance de CacheStorage.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$driver` | `CacheDriver` | Driver de cache (défaut: Files) |
| `$config` | `CacheConfigRecord|null` | Configuration du cache |
| `$cacheKeyPrefix` | `string` | Préfixe des clés (défaut: 'storage_') |

**Retourne :** `CacheStorage` - Storage avec cache

**Exemple :**
```php
$storage = $factory->createCacheStorage(CacheDriver::SQLITE);
```

---

### `createDefaultCacheStorage(): CacheStorage`

Crée un CacheStorage avec la configuration Files par défaut.

**Retourne :** `CacheStorage` - Storage avec cache Files

**Exemple :**
```php
$storage = $factory->createDefaultCacheStorage();
```

---

### `createSqliteCacheStorage(): CacheStorage`

Crée un CacheStorage avec le driver SQLite.

**Retourne :** `CacheStorage` - Storage avec cache SQLite

**Exemple :**
```php
$storage = $factory->createSqliteCacheStorage();
```

---

### `setBasePath(string $basePath): self`

Définit le chemin de base pour les storages persistants.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$basePath` | `string` | Nouveau chemin de base |

**Retourne :** `self` - Interface fluide

**Exemple :**
```php
$factory->setBasePath('/new/path');
```

---

### `setTTL(int $ttl): self`

Définit la durée de vie globale.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$ttl` | `int` | Durée de vie en secondes |

**Retourne :** `self` - Interface fluide

**Exemple :**
```php
$factory->setTTL(7200);
```

---

### `setHashLevels(int $hashLevels): self`

Définit le nombre de niveaux de hachage pour JSONL.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$hashLevels` | `int` | Nombre de niveaux de hachage |

**Retourne :** `self` - Interface fluide

**Exemple :**
```php
$factory->setHashLevels(3);
```

---

### `getBasePath(): string`

Récupère le chemin de base.

**Retourne :** `string` - Chemin de base

**Exemple :**
```php
$path = $factory->getBasePath();
```

---

### `getTTL(): int`

Récupère la durée de vie globale.

**Retourne :** `int` - Durée de vie en secondes

**Exemple :**
```php
$ttl = $factory->getTTL();
```

---

### `getHashLevels(): int`

Récupère le nombre de niveaux de hachage.

**Retourne :** `int` - Niveaux de hachage

**Exemple :**
```php
$levels = $factory->getHashLevels();
```

## Cas d'utilisation

### Cas 1 : Configuration selon l'environnement

```php
class StorageManager
{
    private StorageFactory $factory;
    
    public function __construct(string $env)
    {
        $this->factory = new StorageFactory();
        
        if ($env === 'testing') {
            $this->factory->setBasePath('/tmp/test');
        } elseif ($env === 'production') {
            $this->factory->setBasePath('/var/data');
            $this->factory->setTTL(86400);
        }
    }
    
    public function getStorage(string $type): StorageInterface
    {
        return match ($type) {
            'memory' => $this->factory->createMemoryStorage(),
            'cache' => $this->factory->createCacheStorage(),
            default => $this->factory->createJsonlStorage(),
        };
    }
}
```

### Cas 2 : Migration entre systèmes de stockage

```php
class DataMigrator
{
    private StorageFactory $factory;
    
    public function __construct()
    {
        $this->factory = new StorageFactory('/var/data', 3600);
    }
    
    public function migrate(string $key, StorageSystem $from, StorageSystem $to): void
    {
        $source = $this->factory->create($from);
        $target = $this->factory->create($to);
        
        $data = $source->get($key);
        
        if ($data !== null) {
            $target->set($key, $data);
            $source->delete($key);
        }
    }
}
```

### Cas 3 : Storage avec configuration avancée

```php
// Création d'un cache avec configuration personnalisée
$config = new CacheConfigRecord(
    path: '/custom/cache/path'
);

$cache = $factory->createCacheStorage(
    driver: CacheDriver::SQLITE,
    config: $config,
    cacheKeyPrefix: 'app_'
);
```

## Flux d'exécution

```
create(StorageSystem $system)
    ↓
match($system)
    ├── MEMORY → createMemoryStorage()
    ├── JSONL → createJsonlStorage()
    └── CACHE → createCacheStorage()
```

```
createJsonlStorage()
    ↓
new JsonlStorage(
    basePath: $this->basePath,
    ttl: $this->ttl,
    hashLevels: $this->hashLevels
)
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Driver PhpFastCache non supporté | `PhpfastcacheInvalidConfigurationException` | `Configuration option "path" is not supported` |
| Permissions insuffisantes | `PhpfastcacheIOException` | `The directory ... could not be created` |

**Note :** La plupart des erreurs sont gérées par PhpFastCache et levées comme exceptions.

## Intégration

### Avec Laravel

```php
// config/algokit.php
'storage' => [
    'default' => env('STORAGE_SYSTEM', 'jsonl'),
    'paths' => [
        'base' => env('STORAGE_PATH', storage_path('algokit')),
        'cache' => env('CACHE_PATH', storage_path('algokit/cache')),
    ],
    'ttl' => env('STORAGE_TTL', 86400),
    'hash_levels' => env('STORAGE_HASH_LEVELS', 2),
],

// AppServiceProvider
public function register(): void
{
    $this->app->singleton(StorageFactory::class, function ($app) {
        $config = config('algokit.storage');
        
        return new StorageFactory(
            basePath: $config['paths']['base'],
            ttl: $config['ttl'],
            hashLevels: $config['hash_levels']
        );
    });
}
```

### Avec AlgoKIT

```php
use AndyDefer\AlgoKIT\Algorithms\Trie;
use AndyDefer\StorageKit\Factory\StorageFactory;
use AndyDefer\StorageKit\Enums\StorageSystem;

$factory = new StorageFactory('/var/data');
$storage = $factory->create(StorageSystem::JSONL);

$trie = new Trie($storage, 'autocomplete');
$trie->insert('laravel');
```

## Performance

| Opération | Complexité | Notes |
|-----------|------------|-------|
| `create()` | O(1) | Simple instanciation |
| `createMemoryStorage()` | O(1) | Instanciation directe |
| `createJsonlStorage()` | O(1) | Instanciation avec paramètres |
| `createCacheStorage()` | O(1) | Instanciation PhpFastCache |

**Coût :** La création est légère, mais PhpFastCache peut initialiser des ressources (connexions, fichiers).

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

use AndyDefer\StorageKit\Factory\StorageFactory;
use AndyDefer\StorageKit\Enums\StorageSystem;
use AndyDefer\StorageKit\Enums\CacheDriver;
use AndyDefer\StorageKit\Records\CacheConfigRecord;

// 1. Création de la factory
$factory = new StorageFactory(
    basePath: '/var/data',
    ttl: 3600,
    hashLevels: 2
);

// 2. Création de différents storages
$memory = $factory->create(StorageSystem::MEMORY);
$jsonl = $factory->create(StorageSystem::JSONL);
$cache = $factory->create(StorageSystem::CACHE);

// 3. Configuration personnalisée
$factory->setBasePath('/custom/path');
$factory->setTTL(7200);
$factory->setHashLevels(3);

// 4. Création de storages configurés
$customJsonl = $factory->createJsonlStorage();
$customCache = $factory->createCacheStorage();

// 5. Cache avec configuration avancée
$config = new CacheConfigRecord('/custom/cache');
$sqliteCache = $factory->createCacheStorage(
    driver: CacheDriver::SQLITE,
    config: $config,
    cacheKeyPrefix: 'app_'
);

// 6. Création rapide
$defaultCache = $factory->createDefaultCacheStorage();
$sqliteDefault = $factory->createSqliteCacheStorage();

// 7. Utilisation
$storage = $factory->create(StorageSystem::JSONL);
$storage->set('user_123', ['name' => 'John']);
$user = $storage->get('user_123');

// 8. Lecture de la configuration
echo "Base path: " . $factory->getBasePath();
echo "TTL: " . $factory->getTTL();
echo "Hash levels: " . $factory->getHashLevels();
```

## Voir aussi

- `StorageFactoryInterface` - Interface de la factory
- `StorageInterface` - Interface de stockage
- `StorageSystem` - Enum des systèmes de stockage
- `MemoryStorage` - Stockage en mémoire
- `JsonlStorage` - Stockage JSONL
- `CacheStorage` - Stockage avec cache
- `CacheDriver` - Enum des drivers de cache