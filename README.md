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
   - [SessionStorage - Stockage en session](#sessionstorage---stockage-en-session)
   - [CookieStorage - Stockage en cookies](#cookiestorage---stockage-en-cookies)
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

**StorageKit** est une bibliothèque PHP qui fournit des adaptateurs de stockage unifiés pour différents types de persistance. Elle propose une interface commune (`StorageInterface`) pour le stockage et la récupération de données, avec cinq implémentations :

| Adaptateur | Description | Cas d'usage |
|------------|-------------|-------------|
| **MemoryStorage** | Stockage en mémoire (RAM) | Tests, développement, données éphémères |
| **JsonlStorage** | Stockage persistant JSONL | Données persistantes, logs structurés |
| **CacheStorage** | Stockage avec cache (PhpFastCache) | Haute performance, TTL, multi-backends |
| **SessionStorage** | Stockage en session PHP | Données utilisateur, authentification |
| **CookieStorage** | Stockage en cookies navigateur | Préférences, données légères côté client |

### Pourquoi StorageKit ?

- ✅ **Interface unifiée** : La même API pour tous les storages
- ✅ **Flexibilité** : Changez de storage sans modifier votre code
- ✅ **Performance** : Choisissez le storage adapté à vos besoins
- ✅ **Persistance** : Avec JSONL, Cache, Session ou Cookie, vos données survivent aux requêtes
- ✅ **TTL** : Gérez l'expiration des données (CacheStorage, JsonlStorage)
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
- **SessionStorage** : La session PHP doit être démarrée (`session_start()`)

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

**Cas d'usage :**
- Cache de résultats d'API dans une requête
- Compteur en mémoire
- Cache de données de formulaire
- Cache de calculs lourds

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
    ttl: 3600,
    hashLevels: 2
);

// Stockage
$storage->set('user_123', ['name' => 'John', 'email' => 'john@example.com']);

// Récupération
$user = $storage->get('user_123');

// Batch
$storage->setMultiple([
    'user_456' => ['name' => 'Jane'],
    'user_789' => ['name' => 'Bob'],
]);

$users = $storage->getMultiple(['user_123', 'user_456']);

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

// Suppression
$storage->delete('user_123');
$storage->deleteMultiple(['user_456', 'user_789']);

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

// Batch
$storage->setMultiple([
    'config' => ['debug' => true],
    'settings' => ['theme' => 'dark'],
]);

// Modification du TTL
$storage->setTTL('user_123', 7200);

// Statistiques
$stats = $storage->getStats();
echo "Hits: {$stats->hits}, Misses: {$stats->misses}";
echo "Taux de hit: " . ($stats->hits / ($stats->hits + $stats->misses) * 100) . "%";

// Suppression
$storage->delete('user_123');
$storage->deleteMultiple(['config', 'settings']);

// Nettoyage complet
$storage->clear();
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

### SessionStorage - Stockage en session

**Description :** Stocke les données dans la session PHP (`$_SESSION`). Les données persistent pendant toute la session utilisateur.

**Caractéristiques :**
- 🔒 Persistant pendant la session utilisateur
- 🏷️ Isolation par namespace
- 📦 Stockage côté serveur
- ✅ Support de tous les types PHP
- ⚡ Accès rapide (mémoire)

**Prérequis :**
```php
session_start(); // La session doit être démarrée avant utilisation
```

**Utilisation :**

```php
session_start();

use AndyDefer\StorageKit\Storage\SessionStorage;

// Création avec namespace personnalisé
$storage = new SessionStorage('user_data');

// Stockage
$storage->set('user_id', 123);
$storage->set('preferences', ['theme' => 'dark', 'language' => 'fr']);

// Récupération
$userId = $storage->get('user_id'); // 123
$preferences = $storage->get('preferences'); // ['theme' => 'dark', 'language' => 'fr']

// Batch operations
$storage->setMultiple([
    'username' => 'john_doe',
    'role' => 'admin',
]);

$values = $storage->getMultiple(['username', 'role']);

// Vérification d'existence
if ($storage->exists('user_id')) {
    echo "Utilisateur connecté";
}

// Récupération de toutes les données du namespace
$allData = $storage->getAll();

// Vérification si vide
if ($storage->isEmpty()) {
    echo "Aucune donnée dans ce namespace";
}

// Changement de namespace (les données sont conservées)
$storage->setNamespace('new_namespace');

// Vérification de l'état de la session
if ($storage->isSessionActive()) {
    echo "Session active";
}

// Suppression
$storage->delete('user_id');
$storage->deleteMultiple(['username', 'role']);

// Nettoyage complet
$storage->clear(); // Vide tout le namespace
```

**Cas d'usage :**
- Authentification utilisateur
- Panier d'achat
- Données de formulaire multi-étapes
- État de l'application par utilisateur
- Messages flash

---

### CookieStorage - Stockage en cookies

**Description :** Stocke les données dans les cookies du navigateur. Les données persistent sur le poste du client.

**Caractéristiques :**
- 🌐 Stocké côté client
- ⏰ Expiration configurable
- 🔒 Options de sécurité (Secure, HttpOnly, SameSite)
- 📦 Taille limitée (~4KB par cookie)
- 🔄 Nombre de cookies limité (~50-150 par domaine)

**Utilisation :**

```php
use AndyDefer\StorageKit\Storage\CookieStorage;

// Création avec configuration
$storage = new CookieStorage(
    prefix: 'app_',
    expires: '+30 days',
    domain: null,
    path: '/',
    secure: false,
    httpOnly: true,
    sameSite: 'Lax'
);

// Récupération du préfixe
$prefix = $storage->getPrefix(); // 'app_'

// Stockage
$storage->set('theme', 'dark');
$storage->set('preferences', ['language' => 'fr', 'notifications' => true]);

// Récupération avec valeur par défaut
$theme = $storage->get('theme', 'light');
$preferences = $storage->get('preferences', []);

// Batch operations
$storage->setMultiple([
    'language' => 'en',
    'timezone' => 'Europe/Paris',
]);

$values = $storage->getMultiple(['language', 'timezone']);

// Vérification d'existence
if ($storage->exists('theme')) {
    echo "Thème: " . $storage->get('theme');
}

// Récupération de tous les cookies du préfixe
$allData = $storage->getAll();

// Vérification si vide
if ($storage->isEmpty()) {
    echo "Aucun cookie avec ce préfixe";
}

// Configuration dynamique des cookies
$storage->setExpires('+1 year');      // Expiration dans 1 an
$storage->setExpires(time() + 3600);  // Expiration dans 1 heure
$storage->setDomain('.example.com');   // Domaine
$storage->setPath('/admin');           // Chemin
$storage->setSecure(true);             // HTTPS uniquement
$storage->setHttpOnly(false);          // Accessible en JavaScript
$storage->setSameSite('Strict');       // SameSite

// Récupération de la configuration
$expires = $storage->getExpires();
$domain = $storage->getDomain();
$path = $storage->getPath();
$isSecure = $storage->isSecure();
$isHttpOnly = $storage->isHttpOnly();
$sameSite = $storage->getSameSite();

// Stockage multiple avec la configuration actuelle
$storage->setMultipleWithConfig([
    'session_id' => 'abc123',
    'user_role' => 'admin',
]);

// Suppression
$storage->delete('theme');
$storage->deleteMultiple(['language', 'timezone']);

// Nettoyage complet (supprime tous les cookies du préfixe)
$storage->clear();
```

**Cas d'usage :**
- Préférences utilisateur (thème, langue)
- Panier d'achat léger
- Paramètres d'affichage
- Suivi de session simple
- Cookies de consentement (RGPD)
- Mémorisation des choix utilisateur

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
$session = $factory->create(StorageSystem::SESSION);
$cookie = $factory->create(StorageSystem::COOKIE);
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

// Cookie personnalisé
$cookie = $factory->createCookieStorage(
    prefix: 'app_',
    expires: '+1 year',
    domain: '.example.com',
    path: '/',
    secure: true,
    httpOnly: true,
    sameSite: 'Strict'
);

// Session avec namespace personnalisé
$session = $factory->createSessionStorage('user_preferences');
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

### Préférences utilisateur (CookieStorage)

```php
class UserPreferences
{
    private CookieStorage $storage;
    
    public function __construct()
    {
        $this->storage = new CookieStorage('pref_', '+1 year');
    }
    
    public function setTheme(string $theme): void
    {
        $this->storage->set('theme', $theme);
    }
    
    public function getTheme(): string
    {
        return $this->storage->get('theme', 'light');
    }
    
    public function setLanguage(string $lang): void
    {
        $this->storage->set('lang', $lang);
    }
    
    public function getLanguage(): string
    {
        return $this->storage->get('lang', 'en');
    }
    
    public function setNotifications(bool $enabled): void
    {
        $this->storage->set('notifications', $enabled);
    }
    
    public function getNotifications(): bool
    {
        return $this->storage->get('notifications', true);
    }
    
    public function clear(): void
    {
        $this->storage->clear();
    }
}

// Utilisation
$prefs = new UserPreferences();
$prefs->setTheme('dark');
$prefs->setLanguage('fr');
$prefs->setNotifications(false);

echo $prefs->getTheme(); // 'dark'
echo $prefs->getLanguage(); // 'fr'
var_dump($prefs->getNotifications()); // false
```

### Session utilisateur (SessionStorage)

```php
class UserSession
{
    private SessionStorage $storage;
    
    public function __construct()
    {
        session_start();
        $this->storage = new SessionStorage('user_session');
    }
    
    public function login(int $userId, string $username, string $role): void
    {
        $this->storage->setMultiple([
            'user_id' => $userId,
            'username' => $username,
            'role' => $role,
            'login_time' => time(),
            'is_authenticated' => true,
        ]);
    }
    
    public function isAuthenticated(): bool
    {
        return $this->storage->get('is_authenticated', false);
    }
    
    public function getUserId(): ?int
    {
        return $this->storage->get('user_id');
    }
    
    public function getUsername(): ?string
    {
        return $this->storage->get('username');
    }
    
    public function getRole(): ?string
    {
        return $this->storage->get('role');
    }
    
    public function getLoginTime(): ?int
    {
        return $this->storage->get('login_time');
    }
    
    public function logout(): void
    {
        $this->storage->clear();
    }
}

// Utilisation
$session = new UserSession();
$session->login(123, 'john_doe', 'admin');

if ($session->isAuthenticated()) {
    echo "Bienvenue " . $session->getUsername();
    echo "Rôle: " . $session->getRole();
}
```

### Panier d'achat en cookies

```php
class CookieCart
{
    private CookieStorage $storage;
    
    public function __construct()
    {
        $this->storage = new CookieStorage('cart_', '+7 days');
    }
    
    public function addItem(string $productId, int $quantity = 1): void
    {
        $cart = $this->storage->get('items', []);
        $cart[$productId] = ($cart[$productId] ?? 0) + $quantity;
        $this->storage->set('items', $cart);
    }
    
    public function removeItem(string $productId): void
    {
        $cart = $this->storage->get('items', []);
        unset($cart[$productId]);
        $this->storage->set('items', $cart);
    }
    
    public function getItems(): array
    {
        return $this->storage->get('items', []);
    }
    
    public function getTotalItems(): int
    {
        return array_sum($this->getItems());
    }
    
    public function getItemCount(): int
    {
        return count($this->getItems());
    }
    
    public function clear(): void
    {
        $this->storage->delete('items');
    }
}

// Utilisation
$cart = new CookieCart();
$cart->addItem('p1', 2);
$cart->addItem('p2', 1);
$cart->addItem('p1', 1);

echo $cart->getTotalItems(); // 4
echo $cart->getItemCount(); // 2
print_r($cart->getItems()); // ['p1' => 3, 'p2' => 1]
```

---

## Performance

### Comparatif des storages

| Storage | Vitesse | Persistance | TTL | Mémoire | Cas d'usage |
|---------|---------|-------------|-----|---------|-------------|
| **MemoryStorage** | ⚡⚡⚡ | ❌ | ❌ | 💾 | Tests, éphémère |
| **SessionStorage** | ⚡⚡ | ✅ | ❌ | 💾 | Données utilisateur |
| **CookieStorage** | ⚡⚡ | ✅ | ✅ | 💾 | Préférences client |
| **CacheStorage** | ⚡⚡ | ✅ | ✅ | 💾 | Cache haute perf |
| **JsonlStorage** | 🐌 | ✅ | ✅ | 💾💾 | Persistance |

### Limitations

| Storage | Limitation |
|---------|------------|
| **CookieStorage** | ~4KB par cookie, ~50-150 cookies par domaine |
| **SessionStorage** | Dépend de la configuration PHP (session.gc_maxlifetime) |
| **JsonlStorage** | I/O disque, lent pour de nombreuses écritures |

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
    'session' => [
        'namespace' => env('STORAGE_KIT_SESSION_NAMESPACE', 'storage_kit'),
    ],
    'cookie' => [
        'prefix' => env('STORAGE_KIT_COOKIE_PREFIX', 'storage_'),
        'expires' => env('STORAGE_KIT_COOKIE_EXPIRES', '+30 days'),
        'path' => env('STORAGE_KIT_COOKIE_PATH', '/'),
        'secure' => env('STORAGE_KIT_COOKIE_SECURE', false),
        'http_only' => env('STORAGE_KIT_COOKIE_HTTP_ONLY', true),
        'same_site' => env('STORAGE_KIT_COOKIE_SAME_SITE', 'Lax'),
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

// SessionStorage pour les données utilisateur
session_start();
$session = $factory->create(StorageSystem::SESSION);
$session->set('user_id', 123);

// CookieStorage pour les préférences
$cookie = $factory->create(StorageSystem::COOKIE);
$cookie->set('theme', 'dark');

// Utilisation
$suggestions = $trie->search('la');
$exists = $bloom->exists('https://example.com');
$userId = $session->get('user_id');
$theme = $cookie->get('theme', 'light');
```

---

## Exemples complets

### Système de cache complet avec tous les storages

```php
class MultiStorageSystem
{
    private StorageFactory $factory;
    private StorageInterface $memory;
    private StorageInterface $jsonl;
    private StorageInterface $cache;
    private StorageInterface $session;
    private StorageInterface $cookie;
    
    public function __construct()
    {
        session_start();
        
        $this->factory = new StorageFactory('/var/data', 3600);
        
        $this->memory = $this->factory->create(StorageSystem::MEMORY);
        $this->jsonl = $this->factory->create(StorageSystem::JSONL);
        $this->cache = $this->factory->create(StorageSystem::CACHE);
        $this->session = $this->factory->create(StorageSystem::SESSION);
        $this->cookie = $this->factory->create(StorageSystem::COOKIE);
    }
    
    public function getMemory(): StorageInterface
    {
        return $this->memory;
    }
    
    public function getJsonl(): StorageInterface
    {
        return $this->jsonl;
    }
    
    public function getCache(): StorageInterface
    {
        return $this->cache;
    }
    
    public function getSession(): StorageInterface
    {
        return $this->session;
    }
    
    public function getCookie(): StorageInterface
    {
        return $this->cookie;
    }
}

// Utilisation
$storage = new MultiStorageSystem();

// Données éphémères (Memory)
$storage->getMemory()->set('temp_data', 'value');

// Données persistantes (Jsonl)
$storage->getJsonl()->set('user_profiles', $profiles);

// Données fréquentes (Cache)
$storage->getCache()->setWithTTL('popular_data', $data, 3600);

// Données utilisateur (Session)
$storage->getSession()->set('user_id', 123);

// Préférences (Cookie)
$storage->getCookie()->set('theme', 'dark');
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
            'user' => StorageSystem::SESSION,
            'preferences' => StorageSystem::COOKIE,
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

// Utilisateur (session PHP)
session_start();
$storage->set('user', 'user_id', 123);

// Préférences (cookies)
$storage->set('preferences', 'theme', 'dark');

// Récupération
$user = $storage->get('session', 'user_123');
$theme = $storage->get('preferences', 'theme', 'light');
```

---

## API Reference

### Interfaces

| Interface | Description |
|-----------|-------------|
| `StorageInterface` | Interface de base pour tous les storages |
| `CacheStorageInterface` | Interface étendue pour CacheStorage |
| `JsonlStorageInterface` | Interface étendue pour JsonlStorage |
| `SessionStorageInterface` | Interface étendue pour SessionStorage |
| `CookieStorageInterface` | Interface étendue pour CookieStorage |
| `StorageFactoryInterface` | Interface de la factory |

### Classes

| Classe | Description |
|--------|-------------|
| `MemoryStorage` | Stockage en mémoire |
| `JsonlStorage` | Stockage JSONL |
| `CacheStorage` | Stockage avec cache |
| `SessionStorage` | Stockage en session |
| `CookieStorage` | Stockage en cookies |
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
---