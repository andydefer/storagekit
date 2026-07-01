# StorageKit - Documentation complète

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

## 📖 Table des matières

1. [Introduction](#introduction)
2. [Installation](#installation)
3. [Concepts fondamentaux](#concepts-fondamentaux)
4. [Les adaptateurs de stockage](#les-adaptateurs-de-stockage)
   - [MemoryStorage - Stockage en mémoire](#memorystorage---stockage-en-mémoire)
   - [JsonlStorage - Stockage JSONL](#jsonlstorage---stockage-jsonl)
   - [CacheStorage - Stockage avec cache](#cachestorage---stockage-avec-cache)
5. [La factory](#la-factory)
6. [Cas d'usage réels](#cas-dusage-réels)
   - [Cache de sessions utilisateur](#cache-de-sessions-utilisateur)
   - [Persistance d'état d'application](#persistance-détat-dapplication)
   - [Cache multi-niveaux](#cache-multi-niveaux)
   - [Migration de données](#migration-de-données)
   - [Cache de résultats d'API](#cache-de-résultats-dapi)
7. [Performance](#performance)
8. [Intégration](#intégration)
   - [Avec Laravel](#avec-laravel)
   - [Avec AlgoKIT](#avec-algokit)
9. [Exemples complets](#exemples-complets)
10. [API Reference](#api-reference)

---

## Introduction

**StorageKit** est une bibliothèque PHP qui fournit des adaptateurs de stockage unifiés pour différents types de persistance. Elle propose une interface commune (`StorageInterface`) pour le stockage et la récupération de données, avec trois implémentations :

| Adaptateur | Description | Cas d'usage |
|------------|-------------|-------------|
| **MemoryStorage** | Stockage en mémoire (RAM) | Tests, développement, données éphémères |
| **JsonlStorage** | Stockage persistant JSONL | Données persistantes, logs structurés |
| **CacheStorage** | Stockage avec cache (PhpFastCache) | Haute performance, TTL, multi-backends |

### Pourquoi StorageKit ?

- ✅ **Interface unifiée** : La même API pour tous les storages
- ✅ **Flexibilité** : Changez de storage sans modifier votre code
- ✅ **Performance** : Choisissez le storage adapté à vos besoins
- ✅ **Persistance** : Avec JSONL ou Cache, vos données survivent aux requêtes
- ✅ **TTL** : Gérez l'expiration des données
- ✅ **Statistiques** : Suivez les performances de votre cache
- ✅ **Batch operations** : Optimisez les accès multiples

---

## Installation

```bash
composer require andydefer/storage-kit
```

### Prérequis

- PHP 8.1 ou supérieur
- Extension `json` (activée par défaut)
- (Optionnel) Extension `sqlite3` pour le driver SQLite de CacheStorage

---

## Concepts fondamentaux

### StorageInterface

Tous les storages implémentent la même interface :

```php
interface StorageInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function getMultiple(array $keys): array;
    public function set(string $key, mixed $value): void;
    public function setMultiple(array $items): void;
    public function delete(string $key): bool;
    public function deleteMultiple(array $keys): void;
    public function exists(string $key): bool;
    public function clear(): void;
}
```

### Le pattern de conception

```
┌─────────────────────────────────────────────────────────────┐
│                     StorageInterface                        │
├─────────────────────────────────────────────────────────────┤
│  get()  │  set()  │  delete()  │  exists()  │  clear()    │
│  getMultiple()  │  setMultiple()  │  deleteMultiple()      │
└─────────────────────────────────────────────────────────────┘
                           ▲
                           │
           ┌───────────────┼───────────────┐
           │               │               │
┌──────────▼──────────┐ ┌───▼───────────┐ ┌─▼─────────────┐
│    MemoryStorage    │ │  JsonlStorage │ │  CacheStorage  │
│      (RAM)          │ │    (JSONL)    │ │ (PhpFastCache) │
└─────────────────────┘ └───────────────┘ └───────────────┘
```

---

## Les adaptateurs de stockage

### MemoryStorage - Stockage en mémoire

**Description :** Stocke les données dans un tableau PHP en mémoire RAM.

**Caractéristiques :**
- ⚡ Vitesse maximale
- ❌ Pas de persistance (perdu à la fin du script)
- 💾 Faible consommation mémoire (dépend des données)

**Utilisation :**

```php
use AndyDefer\StorageKit\Storage\MemoryStorage;

$storage = new MemoryStorage();

// Stockage
$storage->set('user_123', ['name' => 'John', 'age' => 30]);

// Récupération
$user = $storage->get('user_123');
// ['name' => 'John', 'age' => 30]

// Batch
$storage->setMultiple([
    'key1' => 'value1',
    'key2' => 'value2',
]);

$values = $storage->getMultiple(['key1', 'key2']);
// ['key1' => 'value1', 'key2' => 'value2']

// Vérification
if ($storage->exists('user_123')) {
    // ...
}

// Suppression
$storage->delete('user_123');

// Nettoyage complet
$storage->clear();
```

**Cas d'usage détaillés :**

#### 1. Cache de résultats d'API

```php
class ApiClient
{
    private MemoryStorage $cache;
    
    public function __construct()
    {
        $this->cache = new MemoryStorage();
    }
    
    public function getUsers(): array
    {
        $cacheKey = 'api_users';
        
        if ($this->cache->exists($cacheKey)) {
            return $this->cache->get($cacheKey);
        }
        
        $users = $this->fetchFromApi('/users');
        $this->cache->set($cacheKey, $users);
        
        return $users;
    }
    
    private function fetchFromApi(string $endpoint): array
    {
        // Simulation d'appel API
        return [['id' => 1, 'name' => 'John']];
    }
}

$api = new ApiClient();
$users = $api->getUsers(); // Appel API
$users = $api->getUsers(); // Cache (plus rapide)
```

#### 2. Compteur en mémoire

```php
class Counter
{
    private MemoryStorage $storage;
    
    public function __construct()
    {
        $this->storage = new MemoryStorage();
        $this->storage->set('counter', 0);
    }
    
    public function increment(): int
    {
        $count = $this->storage->get('counter', 0) + 1;
        $this->storage->set('counter', $count);
        return $count;
    }
    
    public function get(): int
    {
        return $this->storage->get('counter', 0);
    }
    
    public function reset(): void
    {
        $this->storage->set('counter', 0);
    }
}

$counter = new Counter();
echo $counter->increment(); // 1
echo $counter->increment(); // 2
echo $counter->get(); // 2
```

#### 3. Cache de données de formulaire

```php
class FormDataCache
{
    private MemoryStorage $storage;
    private string $sessionId;
    
    public function __construct(string $sessionId)
    {
        $this->storage = new MemoryStorage();
        $this->sessionId = $sessionId;
    }
    
    public function saveFormData(string $formId, array $data): void
    {
        $key = "{$this->sessionId}:form:{$formId}";
        $this->storage->set($key, $data);
    }
    
    public function getFormData(string $formId): ?array
    {
        $key = "{$this->sessionId}:form:{$formId}";
        return $this->storage->get($key);
    }
    
    public function hasFormData(string $formId): bool
    {
        $key = "{$this->sessionId}:form:{$formId}";
        return $this->storage->exists($key);
    }
}

$formCache = new FormDataCache('session_123');
$formCache->saveFormData('registration', ['name' => 'John', 'email' => 'john@example.com']);

if ($formCache->hasFormData('registration')) {
    $data = $formCache->getFormData('registration');
    echo "Nom: " . $data['name'];
}
```

#### 4. Cache de calculs lourds

```php
class HeavyCalculationCache
{
    private MemoryStorage $storage;
    
    public function __construct()
    {
        $this->storage = new MemoryStorage();
    }
    
    public function fibonacci(int $n): int
    {
        $cacheKey = "fib_{$n}";
        
        if ($this->storage->exists($cacheKey)) {
            return $this->storage->get($cacheKey);
        }
        
        $result = $this->calculateFibonacci($n);
        $this->storage->set($cacheKey, $result);
        
        return $result;
    }
    
    private function calculateFibonacci(int $n): int
    {
        if ($n <= 1) return $n;
        return $this->calculateFibonacci($n - 1) + $this->calculateFibonacci($n - 2);
    }
}

$calc = new HeavyCalculationCache();
$result = $calc->fibonacci(35); // Long
$result = $calc->fibonacci(35); // Cache (très rapide)
```

---

### JsonlStorage - Stockage JSONL

**Description :** Stocke les données sur disque au format JSON Lines.

**Caractéristiques :**
- 💾 Persistant sur disque
- 📁 Un fichier par clé (organisation par hash)
- ⏰ Support du TTL global
- 🔄 Support du contexte
- 📊 Statistiques d'utilisation

**Structure des fichiers :**

```
/var/data/
├── a/
│   └── b/
│       └── user_123.jsonl
├── c/
│   └── d/
│       └── session_456.jsonl
└── e/
    └── f/
        └── cache_789.jsonl
```

**Utilisation :**

```php
use AndyDefer\StorageKit\Storage\JsonlStorage;

$storage = new JsonlStorage(
    basePath: '/var/data',
    ttl: 3600,    // 1 heure
    hashLevels: 2 // 2 niveaux de hachage
);

// Stockage
$storage->set('user_123', ['name' => 'John', 'email' => 'john@example.com']);

// Récupération
$user = $storage->get('user_123');

// Vérification d'existence (vérifie aussi l'expiration)
if ($storage->exists('user_123')) {
    echo "User exists and is not expired";
}

// Gestion du TTL global
$storage->setTTL(7200); // Prolonge à 2 heures
$storage->set('session_456', 'active');

// État avec contexte (pour AlgoKIT)
$storage->saveState('trie_french', ['words' => ['bonjour']], 'french');
$state = $storage->loadState('trie_french', 'french');

// Nettoyage des entrées expirées
$deleted = $storage->cleanExpired();
echo "Supprimé {$deleted} entrées expirées";

// Statistiques
$stats = $storage->getStats();
echo "Lignes traitées: " . $stats->total_lines_processed;
echo "Fichiers traités: " . $stats->processed_files;

// Nettoyage complet
$storage->clear();
```

**Format du fichier JSONL :**

```jsonl
{"key":"user_123","value":"{\"value\":{\"name\":\"John\",\"email\":\"john@example.com\"}}","expires_at":"2024-01-15T14:35:00+00:00"}
```

**Cas d'usage :**
- Persistance des données d'application
- Logs structurés
- Cache persistant
- Sauvegarde d'état (AlgoKIT)

---

### CacheStorage - Stockage avec cache

**Description :** Stockage utilisant PhpFastCache avec support de multiples backends.

**Caractéristiques :**
- 🚀 Haute performance
- 💾 Persistant (selon le driver)
- ⏰ TTL par clé
- 📊 Statistiques de performance
- 🔌 Multi-backends (Files, Sqlite)

**Drivers supportés :**

| Driver | Description | Cas d'usage |
|--------|-------------|-------------|
| **Files** | Stockage sur disque | Développement, petite structure |
| **Sqlite** | Base de données SQLite | Performance, grande structure |

**Utilisation avec Files :**

```php
use AndyDefer\StorageKit\Storage\CacheStorage;
use AndyDefer\StorageKit\Enums\CacheDriver;

$storage = new CacheStorage(CacheDriver::FILES);

// Stockage avec TTL
$storage->setWithTTL('user_123', ['name' => 'John'], 3600);

// Récupération
$user = $storage->get('user_123');

// Modification du TTL
$storage->setTTL('user_123', 7200);

// Statistiques
$stats = $storage->getStats();
echo "Hits: {$stats->hits}, Misses: {$stats->misses}";
echo "Taux de hit: " . ($stats->hits / ($stats->hits + $stats->misses) * 100) . "%";

// Nettoyage
$storage->delete('user_123');
```

**Utilisation avec SQLite :**

```php
use AndyDefer\StorageKit\Records\CacheConfigRecord;

$config = new CacheConfigRecord('/tmp/cache.sqlite');
$storage = new CacheStorage(CacheDriver::SQLITE, $config);

$storage->setWithTTL('session_123', 'active', 1800);
$session = $storage->get('session_123');
```

**Cas d'usage :**
- Cache de données
- Sessions utilisateur
- Mise en cache d'API
- Applications haute performance

---

## La factory

**StorageFactory** simplifie la création des storages en centralisant la configuration.

**Utilisation basique :**

```php
use AndyDefer\StorageKit\Factory\StorageFactory;
use AndyDefer\StorageKit\Enums\StorageSystem;

$factory = new StorageFactory(
    basePath: '/var/data',
    ttl: 3600,
    hashLevels: 2
);

// Création de différents storages
$memory = $factory->create(StorageSystem::MEMORY);
$jsonl = $factory->create(StorageSystem::JSONL);
$cache = $factory->create(StorageSystem::CACHE);
```

**Configuration dynamique :**

```php
$factory = new StorageFactory();

$factory->setBasePath('/custom/path');
$factory->setTTL(7200);
$factory->setHashLevels(3);

$storage = $factory->create(StorageSystem::JSONL);
```

**Création avancée :**

```php
use AndyDefer\StorageKit\Enums\CacheDriver;
use AndyDefer\StorageKit\Records\CacheConfigRecord;

// Cache personnalisé
$config = new CacheConfigRecord('/custom/cache');
$cache = $factory->createCacheStorage(
    driver: CacheDriver::SQLITE,
    config: $config,
    cacheKeyPrefix: 'app_'
);

// Méthodes utilitaires
$defaultCache = $factory->createDefaultCacheStorage();
$sqliteCache = $factory->createSqliteCacheStorage();
```

---

## Cas d'usage réels

### Cache de sessions utilisateur

```php
class SessionManager
{
    private CacheStorage $cache;
    private int $ttl;
    
    public function __construct(CacheStorage $cache, int $ttl = 3600)
    {
        $this->cache = $cache;
        $this->ttl = $ttl;
    }
    
    public function startSession(string $userId, array $data): void
    {
        $this->cache->setWithTTL('session_' . $userId, $data, $this->ttl);
    }
    
    public function getSession(string $userId): ?array
    {
        return $this->cache->get('session_' . $userId);
    }
    
    public function extendSession(string $userId, int $extraTime = 1800): void
    {
        if ($this->cache->exists('session_' . $userId)) {
            $this->cache->setTTL('session_' . $userId, $extraTime);
        }
    }
    
    public function endSession(string $userId): void
    {
        $this->cache->delete('session_' . $userId);
    }
    
    public function getStats(): array
    {
        $stats = $this->cache->getStats();
        return [
            'active_sessions' => $stats->items_count,
            'hits' => $stats->hits,
            'misses' => $stats->misses,
        ];
    }
}

// Utilisation
$cache = new CacheStorage(CacheDriver::FILES);
$sessions = new SessionManager($cache, 7200);

$sessions->startSession('user_123', [
    'username' => 'john_doe',
    'login_time' => time(),
]);

$session = $sessions->getSession('user_123');

if ($session) {
    echo "Welcome back, " . $session['username'];
}

$sessions->extendSession('user_123', 3600);
```

### Persistance d'état d'application

```php
class ApplicationState
{
    private JsonlStorage $storage;
    
    public function __construct(JsonlStorage $storage)
    {
        $this->storage = $storage;
    }
    
    public function saveState(string $context, array $state): void
    {
        $this->storage->saveState('app_state', $state, $context);
    }
    
    public function loadState(string $context): ?array
    {
        return $this->storage->loadState('app_state', $context);
    }
    
    public function getAvailableContexts(): array
    {
        // Dans un vrai système, vous pourriez scanner les fichiers
        return ['french', 'english', 'spanish'];
    }
    
    public function backup(): string
    {
        $backupId = 'backup_' . date('Y-m-d_H-i-s');
        $state = $this->storage->get('app_state');
        
        if ($state !== null) {
            $this->storage->saveState($backupId, $state, 'backup');
            return $backupId;
        }
        
        return '';
    }
    
    public function restore(string $backupId): bool
    {
        $state = $this->storage->loadState($backupId, 'backup');
        
        if ($state !== null) {
            $this->storage->saveState('app_state', $state, 'current');
            return true;
        }
        
        return false;
    }
}

// Utilisation
$storage = new JsonlStorage('/var/data', 3600);
$appState = new ApplicationState($storage);

// Sauvegarde d'un état
$appState->saveState('french', [
    'trie' => ['words' => ['bonjour', 'salut', 'au_revoir']],
    'metadata' => ['language' => 'fr', 'version' => '1.0']
]);

// Récupération
$state = $appState->loadState('french');
print_r($state);

// Backup
$backupId = $appState->backup();
echo "Backup créé: {$backupId}";

// Restauration
$appState->restore($backupId);
```

### Cache multi-niveaux

```php
class MultiLevelCache
{
    private MemoryStorage $l1;
    private CacheStorage $l2;
    private array $stats = ['l1_hits' => 0, 'l2_hits' => 0, 'misses' => 0];
    
    public function __construct()
    {
        $this->l1 = new MemoryStorage();
        $this->l2 = new CacheStorage(CacheDriver::FILES);
    }
    
    public function get(string $key, mixed $default = null): mixed
    {
        // L1: Mémoire
        if ($this->l1->exists($key)) {
            $this->stats['l1_hits']++;
            return $this->l1->get($key);
        }
        
        // L2: Cache
        if ($this->l2->exists($key)) {
            $value = $this->l2->get($key);
            $this->l1->set($key, $value);
            $this->stats['l2_hits']++;
            return $value;
        }
        
        $this->stats['misses']++;
        return $default;
    }
    
    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        $this->l1->set($key, $value);
        $this->l2->setWithTTL($key, $value, $ttl);
    }
    
    public function delete(string $key): void
    {
        $this->l1->delete($key);
        $this->l2->delete($key);
    }
    
    public function getStats(): array
    {
        $total = array_sum($this->stats);
        return [
            'l1_hits' => $this->stats['l1_hits'],
            'l2_hits' => $this->stats['l2_hits'],
            'misses' => $this->stats['misses'],
            'hit_rate' => $total > 0 ? (($this->stats['l1_hits'] + $this->stats['l2_hits']) / $total * 100) : 0,
        ];
    }
}

// Utilisation
$cache = new MultiLevelCache();

$cache->set('user_123', ['name' => 'John'], 300);

// Première lecture: L2 (cache)
$user = $cache->get('user_123');

// Deuxième lecture: L1 (mémoire) - plus rapide
$user = $cache->get('user_123');

// Statistiques
$stats = $cache->getStats();
echo "Taux de hit: {$stats['hit_rate']}%";
```

### Migration de données

```php
class DataMigrator
{
    private StorageFactory $factory;
    
    public function __construct(StorageFactory $factory)
    {
        $this->factory = $factory;
    }
    
    public function migrate(string $key, StorageSystem $from, StorageSystem $to): bool
    {
        $source = $this->factory->create($from);
        $target = $this->factory->create($to);
        
        $data = $source->get($key);
        
        if ($data === null) {
            return false;
        }
        
        $target->set($key, $data);
        $source->delete($key);
        
        return true;
    }
    
    public function batchMigrate(array $keys, StorageSystem $from, StorageSystem $to): array
    {
        $source = $this->factory->create($from);
        $target = $this->factory->create($to);
        
        $results = [];
        
        foreach ($keys as $key) {
            $data = $source->get($key);
            
            if ($data !== null) {
                $target->set($key, $data);
                $source->delete($key);
                $results[$key] = 'success';
            } else {
                $results[$key] = 'not_found';
            }
        }
        
        return $results;
    }
}

// Utilisation
$factory = new StorageFactory('/var/data', 3600);
$migrator = new DataMigrator($factory);

// Migration simple
$migrator->migrate('user_123', StorageSystem::MEMORY, StorageSystem::JSONL);

// Migration batch
$keys = ['user_123', 'user_456', 'user_789'];
$results = $migrator->batchMigrate($keys, StorageSystem::JSONL, StorageSystem::CACHE);

foreach ($results as $key => $status) {
    echo "{$key}: {$status}\n";
}
```

### Cache de résultats d'API

```php
class ApiCache
{
    private CacheStorage $cache;
    private int $ttl;
    
    public function __construct(CacheStorage $cache, int $ttl = 300)
    {
        $this->cache = $cache;
        $this->ttl = $ttl;
    }
    
    public function get(string $endpoint, array $params = []): ?array
    {
        $key = $this->buildKey($endpoint, $params);
        
        if ($this->cache->exists($key)) {
            $data = $this->cache->get($key);
            echo "Cache HIT: {$endpoint}\n";
            return $data;
        }
        
        echo "Cache MISS: {$endpoint}\n";
        return null;
    }
    
    public function set(string $endpoint, array $params, array $data): void
    {
        $key = $this->buildKey($endpoint, $params);
        $this->cache->setWithTTL($key, $data, $this->ttl);
        echo "Cached: {$endpoint}\n";
    }
    
    public function getOrFetch(string $endpoint, array $params, callable $fetcher): array
    {
        $key = $this->buildKey($endpoint, $params);
        
        if ($this->cache->exists($key)) {
            echo "Cache HIT: {$endpoint}\n";
            return $this->cache->get($key);
        }
        
        echo "Cache MISS: {$endpoint}\n";
        $data = $fetcher($endpoint, $params);
        $this->cache->setWithTTL($key, $data, $this->ttl);
        
        return $data;
    }
    
    public function clear(string $endpoint): void
    {
        $key = $this->buildKey($endpoint, []);
        $this->cache->delete($key);
    }
    
    private function buildKey(string $endpoint, array $params): string
    {
        $queryString = http_build_query($params);
        return md5($endpoint . '?' . $queryString);
    }
    
    public function getStats(): array
    {
        $stats = $this->cache->getStats();
        return [
            'hits' => $stats->hits,
            'misses' => $stats->misses,
            'hit_rate' => $stats->hits / ($stats->hits + $stats->misses) * 100,
            'items' => $stats->items_count,
        ];
    }
}

// Utilisation
$cache = new ApiCache(
    new CacheStorage(CacheDriver::FILES),
    3600
);

// Récupération avec fetch
$users = $cache->getOrFetch('/api/users', [], function($endpoint, $params) {
    // Appel API réel
    return ['John', 'Jane', 'Bob'];
});

echo "Premier appel: " . print_r($users, true) . "\n";

// Deuxième appel (cache)
$users = $cache->getOrFetch('/api/users', [], function($endpoint, $params) {
    return ['John', 'Jane', 'Bob'];
});

// Statistiques
print_r($cache->getStats());
```

---

## Performance

### Comparatif des storages

| Storage | Vitesse | Persistance | TTL | Mémoire | Cas d'usage |
|---------|---------|-------------|-----|---------|-------------|
| **MemoryStorage** | ⚡⚡⚡ | ❌ | ❌ | 💾 | Tests, éphémère |
| **JsonlStorage** | 🐌 | ✅ | ✅ | 💾💾 | Persistance |
| **CacheStorage** | ⚡⚡ | ✅ | ✅ | 💾 | Production |

### Optimisations

**1. Utilisez MemoryStorage pour les données temporaires**

```php
// ✅ Bon - Données éphémères
$cache = new MemoryStorage();
$cache->set('api_response', $data);
```

**2. Utilisez CacheStorage pour les données fréquentes**

```php
// ✅ Bon - Données fréquemment accédées
$cache = new CacheStorage();
$cache->setWithTTL('popular_data', $data, 3600);
```

**3. Utilisez JsonlStorage pour la persistance**

```php
// ✅ Bon - Données persistantes
$storage = new JsonlStorage('/var/data');
$storage->set('user_profiles', $profiles);
```

**4. Batch operations pour de nombreux éléments**

```php
// ❌ Mauvais - N opérations
foreach ($items as $key => $value) {
    $storage->set($key, $value);
}

// ✅ Bon - 1 opération
$storage->setMultiple($items);
```

**5. Statistiques de performance**

```php
// CacheStorage fournit des statistiques
$stats = $cache->getStats();
echo "Hit rate: " . ($stats->hits / ($stats->hits + $stats->misses) * 100) . "%";

// Utilisez-les pour optimiser votre TTL
if ($stats->misses > $stats->hits * 2) {
    $cache->setTTL('frequent_key', 7200); // Augmentez le TTL
}
```

---

## Intégration

### Avec Laravel

**1. Configuration**

```php
// config/storage-kit.php
return [
    'default' => env('STORAGE_KIT_DEFAULT', 'jsonl'),
    'paths' => [
        'base' => env('STORAGE_KIT_PATH', storage_path('storage-kit')),
        'cache' => env('STORAGE_KIT_CACHE_PATH', storage_path('storage-kit/cache')),
    ],
    'ttl' => env('STORAGE_KIT_TTL', 86400),
    'hash_levels' => env('STORAGE_KIT_HASH_LEVELS', 2),
    'cache' => [
        'driver' => env('STORAGE_KIT_CACHE_DRIVER', 'Files'),
        'prefix' => env('STORAGE_KIT_CACHE_PREFIX', 'storage_'),
    ],
];
```

**2. Service Provider**

```php
// App/Providers/StorageKitServiceProvider.php
namespace App\Providers;

use AndyDefer\StorageKit\Contracts\Storage\StorageInterface;
use AndyDefer\StorageKit\Factory\StorageFactory;
use AndyDefer\StorageKit\Enums\StorageSystem;
use Illuminate\Support\ServiceProvider;

class StorageKitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StorageFactory::class, function ($app) {
            $config = config('storage-kit');
            
            return new StorageFactory(
                basePath: $config['paths']['base'],
                ttl: $config['ttl'],
                hashLevels: $config['hash_levels']
            );
        });

        $this->app->singleton(StorageInterface::class, function ($app) {
            $factory = $app->make(StorageFactory::class);
            $default = config('storage-kit.default', 'jsonl');
            $system = StorageSystem::tryFrom($default) ?? StorageSystem::JSONL;
            
            return $factory->create($system);
        });
    }
}
```

**3. Utilisation dans un Controller**

```php
use AndyDefer\StorageKit\Contracts\Storage\StorageInterface;
use AndyDefer\StorageKit\Factory\StorageFactory;

class UserController extends Controller
{
    private StorageInterface $storage;
    
    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }
    
    public function show(int $userId)
    {
        $cacheKey = 'user_' . $userId;
        
        if ($this->storage->exists($cacheKey)) {
            $user = $this->storage->get($cacheKey);
            return response()->json(['user' => $user, 'cached' => true]);
        }
        
        $user = User::find($userId);
        $this->storage->set($cacheKey, $user->toArray());
        
        return response()->json(['user' => $user, 'cached' => false]);
    }
}
```

### Avec AlgoKIT

```php
use AndyDefer\AlgoKIT\Algorithms\Trie;
use AndyDefer\AlgoKIT\Algorithms\BloomFilter;
use AndyDefer\StorageKit\Factory\StorageFactory;
use AndyDefer\StorageKit\Enums\StorageSystem;

$factory = new StorageFactory('/var/data', 3600);

// Trie avec JSONL (persistant)
$jsonl = $factory->create(StorageSystem::JSONL);
$trie = new Trie($jsonl, 'autocomplete');
$trie->insert('laravel');

// BloomFilter avec Memory (rapide)
$memory = $factory->create(StorageSystem::MEMORY);
$bloom = new BloomFilter($memory, 10000, 3, 'url_index');
$bloom->insert('https://example.com');

// Utilisation
$suggestions = $trie->search('la');
$exists = $bloom->exists('https://example.com');
```

---

## Exemples complets

### Système de cache complet

```php
class CacheSystem
{
    private StorageFactory $factory;
    private StorageInterface $storage;
    
    public function __construct(string $env = 'production')
    {
        $this->factory = new StorageFactory('/var/cache', 3600);
        
        if ($env === 'testing') {
            $this->storage = $this->factory->create(StorageSystem::MEMORY);
        } elseif ($env === 'production') {
            $this->storage = $this->factory->create(StorageSystem::CACHE);
        } else {
            $this->storage = $this->factory->create(StorageSystem::JSONL);
        }
    }
    
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->storage->get($key, $default);
    }
    
    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        if ($ttl !== null && $this->storage instanceof CacheStorage) {
            $this->storage->setWithTTL($key, $value, $ttl);
        } else {
            $this->storage->set($key, $value);
        }
    }
    
    public function exists(string $key): bool
    {
        return $this->storage->exists($key);
    }
    
    public function delete(string $key): bool
    {
        return $this->storage->delete($key);
    }
    
    public function clear(): void
    {
        $this->storage->clear();
    }
    
    public function getStats(): array
    {
        if ($this->storage instanceof CacheStorage) {
            $stats = $this->storage->getStats();
            return [
                'hits' => $stats->hits,
                'misses' => $stats->misses,
                'items' => $stats->items_count,
                'driver' => $stats->driver->value,
            ];
        }
        
        return ['type' => get_class($this->storage)];
    }
}

// Utilisation
$cache = new CacheSystem('production');

$cache->set('user_123', ['name' => 'John', 'email' => 'john@example.com'], 3600);

if ($cache->exists('user_123')) {
    $user = $cache->get('user_123');
    echo "Welcome, " . $user['name'];
}

$stats = $cache->getStats();
print_r($stats);
```

### Stockage multi-contexte

```php
class MultiContextStorage
{
    private StorageFactory $factory;
    private array $storages = [];
    
    public function __construct(StorageFactory $factory)
    {
        $this->factory = $factory;
    }
    
    public function getStorage(string $context): StorageInterface
    {
        if (!isset($this->storages[$context])) {
            $this->storages[$context] = $this->factory->create(
                $this->getSystemForContext($context)
            );
        }
        
        return $this->storages[$context];
    }
    
    private function getSystemForContext(string $context): StorageSystem
    {
        return match ($context) {
            'session' => StorageSystem::CACHE,
            'cache' => StorageSystem::MEMORY,
            'persistent' => StorageSystem::JSONL,
            default => StorageSystem::MEMORY,
        };
    }
    
    public function set(string $context, string $key, mixed $value): void
    {
        $this->getStorage($context)->set($key, $value);
    }
    
    public function get(string $context, string $key, mixed $default = null): mixed
    {
        return $this->getStorage($context)->get($key, $default);
    }
}

// Utilisation
$factory = new StorageFactory('/var/data', 3600);
$storage = new MultiContextStorage($factory);

// Session (cache)
$storage->set('session', 'user_123', ['name' => 'John']);

// Cache temporaire (mémoire)
$storage->set('cache', 'api_response', $apiData);

// Persistant (JSONL)
$storage->set('persistent', 'user_profiles', $profiles);

// Récupération
$user = $storage->get('session', 'user_123');
```

---

## API Reference

### Interfaces

| Interface | Description |
|-----------|-------------|
| `StorageInterface` | Interface de base pour tous les storages |
| `CacheStorageInterface` | Interface étendue pour CacheStorage |
| `JsonlStorageInterface` | Interface étendue pour JsonlStorage |
| `StorageFactoryInterface` | Interface de la factory |

### Classes

| Classe | Description |
|--------|-------------|
| `MemoryStorage` | Stockage en mémoire |
| `JsonlStorage` | Stockage JSONL |
| `CacheStorage` | Stockage avec cache |
| `StorageFactory` | Factory de storages |

### Enums

| Enum | Description |
|------|-------------|
| `StorageSystem` | Systèmes de stockage disponibles |
| `CacheDriver` | Drivers de cache disponibles |

### Records

| Record | Description |
|--------|-------------|
| `CacheConfigRecord` | Configuration de CacheStorage |
| `CacheStorageStatsRecord` | Statistiques de CacheStorage |
| `JsonlStorageStatsRecord` | Statistiques de JsonlStorage |
| `JsonlStorageRecord` | Record pour JSONL |

---

## License

MIT © [Andy Kani](https://github.com/andydefer)