# MemoryStorage - Référence Technique

## Description

MemoryStorage est une implémentation de stockage en mémoire utilisant un tableau PHP natif. Les données sont conservées dans la RAM et sont perdues à la fin de l'exécution du script.

## Hiérarchie / Implémentations

```
StorageInterface
    └── MemoryStorage
```

**Interfaces implémentées :** `StorageInterface`

## Rôle principal

MemoryStorage fournit un stockage ultra-rapide en mémoire (RAM) pour les données de l'application. Idéal pour les environnements de test, le développement, et les données éphémères qui n'ont pas besoin de persistance entre les requêtes.

## Installation

```bash
composer require andydefer/storage-kit
```

## API / Méthodes publiques

### `get(string $key, mixed $default = null): mixed`

Récupère une valeur stockée avec une clé donnée.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé d'identification |
| `$default` | `mixed` | Valeur par défaut si la clé n'existe pas |

**Retourne :** `mixed` - La valeur stockée ou la valeur par défaut

**Exemple :**
```php
$storage = new MemoryStorage();
$storage->set('user_name', 'John');
$name = $storage->get('user_name'); // 'John'
$missing = $storage->get('nonexistent', 'default'); // 'default'
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
$result = $storage->getMultiple(['user_123', 'user_456', 'user_789']);
// [
//     'user_123' => ['name' => 'John'],
//     'user_456' => ['name' => 'Jane'],
//     'user_789' => null
// ]
```

---

### `set(string $key, mixed $value): void`

Stocke une valeur avec une clé donnée. Écrase toute valeur existante.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé d'identification |
| `$value` | `mixed` | Valeur à stocker |

**Retourne :** `void`

**Exemple :**
```php
$storage->set('user_123', ['name' => 'John', 'age' => 30]);
$storage->set('counter', 42);
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
    'user_789' => ['name' => 'Bob'],
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
$storage->deleteMultiple(['user_123', 'user_456', 'user_789']);
```

---

### `exists(string $key): bool`

Vérifie si une clé existe.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé à vérifier |

**Retourne :** `bool` - `true` si la clé existe, `false` sinon

**Exemple :**
```php
if ($storage->exists('user_123')) {
    $user = $storage->get('user_123');
}
```

---

### `clear(): void`

Supprime toutes les données stockées.

**Retourne :** `void`

**Exemple :**
```php
$storage->clear(); // Vide tout le storage
```

## Cas d'utilisation

### Cas 1 : Cache de résultats d'API

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
}
```

### Cas 2 : Session utilisateur temporaire

```php
class SessionManager
{
    private MemoryStorage $storage;
    private string $sessionId;
    
    public function __construct(string $sessionId)
    {
        $this->storage = new MemoryStorage();
        $this->sessionId = $sessionId;
    }
    
    public function set(string $key, mixed $value): void
    {
        $this->storage->set($this->sessionId . '_' . $key, $value);
    }
    
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->storage->get($this->sessionId . '_' . $key, $default);
    }
    
    public function clear(): void
    {
        $this->storage->clear();
    }
}
```

### Cas 3 : Compteur en mémoire

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

// Utilisation
$counter = new Counter();
echo $counter->increment(); // 1
echo $counter->increment(); // 2
echo $counter->get(); // 2
```

## Flux d'exécution

```
set($key, $value)
    ↓
$this->data[$key] = $value
    ↓
get($key, $default)
    ↓
return $this->data[$key] ?? $default
    ↓
exists($key)
    ↓
return array_key_exists($key, $this->data)
    ↓
delete($key)
    ↓
if array_key_exists → unset → return true
else → return false
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Aucune exception explicite | - | - |

**Note :** MemoryStorage ne lève pas d'exceptions. Les erreurs sont gérées silencieusement :
- `get()` retourne `null` ou la valeur par défaut si la clé n'existe pas
- `delete()` retourne `false` si la clé n'existe pas
- `exists()` retourne `false` si la clé n'existe pas

## Intégration

### Avec StorageFactory

MemoryStorage est créé via la factory :

```php
use AndyDefer\StorageKit\Factory\StorageFactory;
use AndyDefer\StorageKit\Enums\StorageSystem;

$factory = new StorageFactory();
$storage = $factory->create(StorageSystem::MEMORY);
```

### Avec les structures AlgoKIT

```php
use AndyDefer\AlgoKIT\Algorithms\Trie;
use AndyDefer\StorageKit\Storage\MemoryStorage;

$storage = new MemoryStorage();
$trie = new Trie($storage, 'autocomplete');
$trie->insert('laravel');
```

## Performance

| Opération | Complexité | Notes |
|-----------|------------|-------|
| `get()` | O(1) | Accès direct au tableau |
| `set()` | O(1) | Écriture directe |
| `exists()` | O(1) | Vérification directe |
| `delete()` | O(1) | Suppression directe |
| `getMultiple()` | O(n) | n = nombre de clés |
| `setMultiple()` | O(n) | n = nombre d'éléments |
| `deleteMultiple()` | O(n) | n = nombre de clés |
| `clear()` | O(1) | Réinitialisation du tableau |

**Mémoire :** Les données sont conservées en RAM pendant toute la durée de vie du script.

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

use AndyDefer\StorageKit\Storage\MemoryStorage;

// 1. Création
$storage = new MemoryStorage();

// 2. Stockage de différents types
$storage->set('string', 'Hello World');
$storage->set('integer', 42);
$storage->set('float', 3.14);
$storage->set('boolean', true);
$storage->set('array', ['a', 'b', 'c']);
$storage->set('null', null);

// 3. Récupération
echo $storage->get('string'); // 'Hello World'
echo $storage->get('integer'); // 42
echo $storage->get('float'); // 3.14
var_dump($storage->get('boolean')); // true
print_r($storage->get('array')); // ['a', 'b', 'c']
var_dump($storage->get('null')); // null

// 4. Vérification d'existence
if ($storage->exists('string')) {
    echo "Clé 'string' existe";
}

// 5. Batch operations
$storage->setMultiple([
    'key1' => 'value1',
    'key2' => 'value2',
    'key3' => 'value3',
]);

$values = $storage->getMultiple(['key1', 'key2', 'key3']);
print_r($values);

// 6. Suppression
$storage->delete('key1');
$storage->deleteMultiple(['key2', 'key3']);

// 7. Nettoyage
$storage->clear();
```

## Voir aussi

- `StorageInterface` - Interface de stockage
- `StorageFactory` - Factory pour créer des storages
- `JsonlStorage` - Stockage persistant JSONL
- `CacheStorage` - Stockage avec cache
- `Trie` - Structure de données utilisant MemoryStorage