# JsonlStorage - Référence Technique

## Description

JsonlStorage est une implémentation de stockage persistant utilisant le format JSON Lines (JSONL). Chaque clé est stockée dans un fichier JSONL séparé, avec support du TTL et des contextes.

## Hiérarchie / Implémentations

```
JsonlStorageInterface
    └── JsonlStorage
```

**Interfaces implémentées :** `StorageInterface`, `JsonlStorageInterface`

## Rôle principal

JsonlStorage assure la persistance des données sur disque au format JSON Lines. Chaque valeur est stockée dans un fichier individuel, organisé via une stratégie de hachage pour une distribution équilibrée. Idéal pour les applications nécessitant une persistance simple et fiable.

## Installation

```bash
composer require andydefer/storage-kit
```

## API / Méthodes publiques

### `__construct(string $basePath, int $ttl = 86400, int $hashLevels = 2)`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$basePath` | `string` | Chemin de base pour le stockage |
| `$ttl` | `int` | Durée de vie globale en secondes (défaut: 86400) |
| `$hashLevels` | `int` | Niveaux de hachage pour l'organisation des fichiers (défaut: 2) |

**Retourne :** `void`

**Exemple :**
```php
$storage = new JsonlStorage('/var/data/storage', 3600, 2);
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

### `delete(string $key): bool`

Supprime une valeur par sa clé.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé à supprimer |

**Retourne :** `bool` - `true` si la clé existait et a été supprimée, `false` sinon

**Exemple :**
```php
$deleted = $storage->delete('user_123'); // true
$notDeleted = $storage->delete('nonexistent'); // false
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

### `clear(): void`

Supprime tous les fichiers JSONL du storage.

**Retourne :** `void`

**Exemple :**
```php
$storage->clear(); // Vide tout le storage
```

---

### `saveState(string $key, array $state, ?string $context = null): void`

Sauvegarde un état avec support de contexte.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé d'identification |
| `$state` | `array` | État à sauvegarder |
| `$context` | `string|null` | Contexte optionnel pour l'isolation |

**Retourne :** `void`

**Exemple :**
```php
$storage->saveState('trie', ['words' => ['laravel']], 'french');
```

---

### `loadState(string $key, ?string $context = null): ?array`

Récupère un état sauvegardé.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé d'identification |
| `$context` | `string|null` | Contexte optionnel |

**Retourne :** `array|null` - L'état récupéré ou `null`

**Exemple :**
```php
$state = $storage->loadState('trie', 'french');
```

---

### `setTTL(int $seconds): void`

Définit la durée de vie globale des données.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$seconds` | `int` | Durée de vie en secondes (0 = pas d'expiration) |

**Retourne :** `void`

**Exemple :**
```php
$storage->setTTL(3600); // 1 heure
```

---

### `getTTL(): int`

Récupère la durée de vie globale.

**Retourne :** `int` - Durée de vie en secondes

**Exemple :**
```php
$ttl = $storage->getTTL(); // 3600
```

---

### `cleanExpired(): int`

Supprime toutes les entrées expirées.

**Retourne :** `int` - Nombre d'entrées supprimées

**Exemple :**
```php
$deleted = $storage->cleanExpired(); // 42
```

---

### `getStats(): JsonlStorageStatsRecord`

Récupère les statistiques du storage.

**Retourne :** `JsonlStorageStatsRecord` - Statistiques du storage

**Exemple :**
```php
$stats = $storage->getStats();
echo "Lignes traitées: " . $stats->total_lines_processed;
```

---

### `getJsonlService(): JsonlService`

Récupère le service JSONL sous-jacent.

**Retourne :** `JsonlService` - Service JSONL

**Exemple :**
```php
$jsonl = $storage->getJsonlService();
```

## Cas d'utilisation

### Cas 1 : Stockage de profils utilisateurs

```php
class UserRepository
{
    private JsonlStorage $storage;
    
    public function __construct(JsonlStorage $storage)
    {
        $this->storage = $storage;
    }
    
    public function save(User $user): void
    {
        $this->storage->set('user_' . $user->id, $user->toArray());
    }
    
    public function find(int $id): ?User
    {
        $data = $this->storage->get('user_' . $id);
        return $data ? User::fromArray($data) : null;
    }
    
    public function delete(int $id): bool
    {
        return $this->storage->delete('user_' . $id);
    }
}
```

### Cas 2 : Cache de résultats d'API avec TTL

```php
class ApiCache
{
    private JsonlStorage $storage;
    
    public function __construct(JsonlStorage $storage)
    {
        $this->storage = $storage;
    }
    
    public function get(string $endpoint, int $ttl = 300): ?array
    {
        $this->storage->setTTL($ttl);
        return $this->storage->get($endpoint);
    }
    
    public function set(string $endpoint, array $data): void
    {
        $this->storage->set($endpoint, $data);
    }
}
```

### Cas 3 : Sauvegarde d'état d'application

```php
class ApplicationState
{
    private JsonlStorage $storage;
    
    public function __construct(JsonlStorage $storage)
    {
        $this->storage = $storage;
    }
    
    public function save(string $context, array $state): void
    {
        $this->storage->saveState('app_state', $state, $context);
    }
    
    public function load(string $context): ?array
    {
        return $this->storage->loadState('app_state', $context);
    }
    
    public function clear(string $context): void
    {
        $this->storage->delete('app_state_' . $context);
    }
}
```

## Flux d'exécution

```
set($key, $value)
    ↓
delete($key)  // Supprime l'ancien fichier
    ↓
sanitizeKey($key)  // Nettoie la clé
    ↓
create CacheJsonlRecord avec value et expires_at
    ↓
service->write($record)  // Écriture JSONL
    ↓
save to disk (KeyBasedPathStrategy)
```

```
get($key)
    ↓
resolveFilePath($key)  // Résout le chemin (avec cache)
    ↓
service->fileExists()  // Vérifie l'existence
    ↓
service->getFirstLine()  // Lit la première ligne
    ↓
check expires_at  // Vérifie l'expiration
    ↓
json_decode value  // Décode la valeur
    ↓
return value
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Fichier inexistant | - | Retourne `$default` |
| Données JSON invalides | - | Retourne `$default` |
| Fichier expiré | - | Supprime et retourne `$default` |

**Note :** JsonlStorage ne lève pas d'exceptions pour les erreurs de lecture. Les erreurs sont gérées silencieusement avec des valeurs par défaut.

## Intégration

### Avec StorageFactory

```php
use AndyDefer\StorageKit\Factory\StorageFactory;
use AndyDefer\StorageKit\Enums\StorageSystem;

$factory = new StorageFactory('/var/data', 3600);
$storage = $factory->create(StorageSystem::JSONL);
```

### Avec les structures AlgoKIT

```php
use AndyDefer\AlgoKIT\Algorithms\Trie;
use AndyDefer\StorageKit\Storage\JsonlStorage;

$storage = new JsonlStorage('/var/data');
$trie = new Trie($storage, 'autocomplete');
$trie->insert('laravel');
```

## Performance

| Opération | Complexité | Notes |
|-----------|------------|-------|
| `get()` | O(1) | Lecture du premier fichier ligne |
| `set()` | O(1) | Écriture d'un seul fichier |
| `delete()` | O(1) | Suppression d'un fichier |
| `clear()` | O(n) | n = nombre de fichiers |
| `cleanExpired()` | O(n) | n = nombre de fichiers |
| `saveState()` | O(1) | Écriture d'un fichier |
| `loadState()` | O(1) | Lecture d'un fichier |

**Buffer :** Écritures bufferisées par `JsonlService` (taille par défaut: 1000 lignes).

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

use AndyDefer\StorageKit\Storage\JsonlStorage;

// 1. Création du storage
$storage = new JsonlStorage('/tmp/storage', 3600, 2);

// 2. Stockage de données
$storage->set('user_123', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

$storage->setMultiple([
    'user_456' => ['name' => 'Jane Doe'],
    'user_789' => ['name' => 'Bob Smith'],
]);

// 3. Récupération
$user = $storage->get('user_123');
$users = $storage->getMultiple(['user_456', 'user_789']);

// 4. Vérification d'existence
if ($storage->exists('user_123')) {
    echo "User 123 exists";
}

// 5. État avec contexte
$storage->saveState('trie_french', ['words' => ['bonjour']], 'french');
$state = $storage->loadState('trie_french', 'french');

// 6. Gestion du TTL
$storage->setTTL(300); // 5 minutes
$storage->set('session_123', 'active');

// 7. Nettoyage
$deleted = $storage->cleanExpired();
echo "Supprimé {$deleted} entrées expirées";

// 8. Statistiques
$stats = $storage->getStats();
echo "Fichiers traités: " . $stats->processed_files;

// 9. Suppression
$storage->delete('user_123');
$storage->deleteMultiple(['user_456', 'user_789']);

// 10. Nettoyage complet
$storage->clear();
```

## Voir aussi

- [`cache-storage`](cache-storage.md) - Stockage cache
- [`cookie-storage`](cookie-storage.md) - Stockage cookie
- [`memory-storage`](memory-storage.md) - Stockage mémoire
- [`session-storage`](session-storage.md) - Stockage session
- [`sqlite-storage`](sqlite-storage.md) - Stockage sqlite