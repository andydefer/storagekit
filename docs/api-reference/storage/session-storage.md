# SessionStorage - Référence Technique

## Description

SessionStorage est une implémentation de stockage utilisant les sessions PHP (`$_SESSION`). Les données sont stockées côté serveur et persistent pendant toute la durée de la session utilisateur.

## Hiérarchie / Implémentations

```
SessionStorageInterface
    └── SessionStorage
```

**Interfaces implémentées :** `StorageInterface`, `SessionStorageInterface`

## Rôle principal

SessionStorage permet de stocker des données dans la session PHP. Il offre un mécanisme d'isolation des données via des namespaces, permettant d'utiliser plusieurs storages indépendants dans la même session. Idéal pour les données utilisateur, les états d'authentification, et les données temporaires qui doivent survivre entre les requêtes.

## Installation

```bash
composer require andydefer/storage-kit
```

### Prérequis

- La session PHP doit être démarrée (`session_start()`) avant d'utiliser SessionStorage

## API / Méthodes publiques

### `__construct(string $namespace = 'storage_kit')`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$namespace` | `string` | Namespace pour l'isolation des données (défaut: 'storage_kit') |

**Retourne :** `void`

**Exceptions :** `RuntimeException` si la session n'est pas démarrée

**Exemple :**
```php
session_start();
$storage = new SessionStorage('app_data');
```

---

### `getNamespace(): string`

Retourne le namespace actuel.

**Retourne :** `string` - Le namespace

**Exemple :**
```php
$namespace = $storage->getNamespace(); // 'app_data'
```

---

### `setNamespace(string $namespace): void`

Change le namespace pour l'isolation des données.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$namespace` | `string` | Nouveau namespace |

**Retourne :** `void`

**Exemple :**
```php
$storage->setNamespace('user_preferences');
```

---

### `isSessionActive(): bool`

Vérifie si la session est active.

**Retourne :** `bool` - `true` si la session est active, `false` sinon

**Exemple :**
```php
if ($storage->isSessionActive()) {
    // La session est active
}
```

---

### `get(string $key, mixed $default = null): mixed`

Récupère une valeur depuis la session.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé de la donnée |
| `$default` | `mixed` | Valeur par défaut si la clé n'existe pas |

**Retourne :** `mixed` - La valeur stockée ou la valeur par défaut

**Exemple :**
```php
$userId = $storage->get('user_id', 0);
$preferences = $storage->get('preferences', []);
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
$values = $storage->getMultiple(['user_id', 'username', 'role']);
```

---

### `set(string $key, mixed $value): void`

Stocke une valeur dans la session.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé de la donnée |
| `$value` | `mixed` | Valeur à stocker |

**Retourne :** `void`

**Exemple :**
```php
$storage->set('user_id', 123);
$storage->set('preferences', ['theme' => 'dark']);
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
    'user_id' => 123,
    'username' => 'john_doe',
    'role' => 'admin',
]);
```

---

### `delete(string $key): bool`

Supprime une valeur de la session.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé à supprimer |

**Retourne :** `bool` - `true` si la clé existait, `false` sinon

**Exemple :**
```php
$storage->delete('session_token');
```

---

### `deleteMultiple(array $keys): void`

Supprime plusieurs valeurs en une seule opération.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$keys` | `string[]` | Liste des clés à supprimer |

**Retourne :** `void`

**Exemple :**
```php
$storage->deleteMultiple(['user_id', 'session_token']);
```

---

### `exists(string $key): bool`

Vérifie si une clé existe dans la session.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé à vérifier |

**Retourne :** `bool` - `true` si la clé existe, `false` sinon

**Exemple :**
```php
if ($storage->exists('user_id')) {
    $userId = $storage->get('user_id');
}
```

---

### `clear(): void`

Supprime toutes les données du namespace.

**Retourne :** `void`

**Exemple :**
```php
$storage->clear(); // Vide tout le namespace
```

---

### `getAll(): array`

Récupère toutes les données du namespace.

**Retourne :** `array<string, mixed>` - Toutes les données du namespace

**Exemple :**
```php
$allData = $storage->getAll();
```

---

### `isEmpty(): bool`

Vérifie si le namespace est vide.

**Retourne :** `bool` - `true` si vide, `false` sinon

**Exemple :**
```php
if ($storage->isEmpty()) {
    // Aucune donnée dans ce namespace
}
```

## Cas d'utilisation

### Cas 1 : Gestion de session utilisateur

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
}
```

### Cas 2 : Panier d'achat en session

```php
class SessionCart
{
    private SessionStorage $storage;
    
    public function __construct()
    {
        session_start();
        $this->storage = new SessionStorage('shopping_cart');
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
    
    public function getTotalPrice(array $prices): float
    {
        $total = 0;
        foreach ($this->getItems() as $productId => $quantity) {
            if (isset($prices[$productId])) {
                $total += $prices[$productId] * $quantity;
            }
        }
        return $total;
    }
    
    public function clear(): void
    {
        $this->storage->delete('items');
    }
}

// Utilisation
$cart = new SessionCart();
$cart->addItem('p1', 2);
$cart->addItem('p2', 1);
$cart->addItem('p1', 1);

echo $cart->getTotalItems(); // 4
print_r($cart->getItems()); // ['p1' => 3, 'p2' => 1]
```

### Cas 3 : Multi-namespace pour contextes séparés

```php
class MultiContextSession
{
    private SessionStorage $userStorage;
    private SessionStorage $appStorage;
    
    public function __construct()
    {
        session_start();
        $this->userStorage = new SessionStorage('user_context');
        $this->appStorage = new SessionStorage('app_context');
    }
    
    public function getUserContext(): SessionStorage
    {
        return $this->userStorage;
    }
    
    public function getAppContext(): SessionStorage
    {
        return $this->appStorage;
    }
    
    public function clearAll(): void
    {
        $this->userStorage->clear();
        $this->appStorage->clear();
    }
}

// Utilisation
$context = new MultiContextSession();

// Données utilisateur
$context->getUserContext()->set('user_id', 123);
$context->getUserContext()->set('preferences', ['theme' => 'dark']);

// Données application
$context->getAppContext()->set('last_page', '/dashboard');
$context->getAppContext()->set('notifications', 5);

// Les contextes sont isolés
echo $context->getUserContext()->get('user_id'); // 123
echo $context->getAppContext()->get('last_page'); // '/dashboard'
```

## Flux d'exécution

```
set($key, $value)
    ↓
getSessionData() → $_SESSION[$namespace] ?? []
    ↓
$data[$key] = $value
    ↓
saveSessionData($data) → $_SESSION[$namespace] = $data
```

```
get($key, $default)
    ↓
getSessionData() → $_SESSION[$namespace] ?? []
    ↓
return $data[$key] ?? $default
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Session non démarrée | `RuntimeException` | `Session must be started before using SessionStorage. Call session_start() first.` |

## Intégration

### Avec StorageFactory

```php
use AndyDefer\StorageKit\Factory\StorageFactory;
use AndyDefer\StorageKit\Enums\StorageSystem;

$factory = new StorageFactory();
$storage = $factory->create(StorageSystem::SESSION);
```

### Avec AlgoKIT

```php
session_start();

use AndyDefer\AlgoKIT\Algorithms\Trie;
use AndyDefer\StorageKit\Storage\SessionStorage;

$storage = new SessionStorage('user_data');
$trie = new Trie($storage, 'autocomplete');
$trie->insert('laravel');
```

## Performance

| Opération | Complexité | Notes |
|-----------|------------|-------|
| `get()` | O(1) | Accès direct à $_SESSION |
| `set()` | O(1) | Écriture directe |
| `getMultiple()` | O(n) | n = nombre de clés |
| `setMultiple()` | O(n) | n = nombre d'éléments |
| `clear()` | O(1) | Réinitialisation du tableau |

**Mémoire :** Les données sont stockées en mémoire côté serveur et sont sérialisées automatiquement par PHP.

## Compatibilité

| Version | Support |
|---------|---------|
| PHP 8.1+ | ✅ Complet |
| PHP 8.0 | ✅ Complet |

## Exemple complet

```php
<?php

declare(strict_types=1);

// Démarrer la session
session_start();

use AndyDefer\StorageKit\Storage\SessionStorage;

// 1. Création
$storage = new SessionStorage('app_data');

// 2. Stockage de différents types
$storage->set('string', 'Hello World');
$storage->set('integer', 42);
$storage->set('boolean', true);
$storage->set('array', ['a' => 1, 'b' => 2]);
$storage->set('object', new stdClass());
$storage->set('null', null);

// 3. Récupération
echo $storage->get('string'); // 'Hello World'
echo $storage->get('integer'); // 42
var_dump($storage->get('boolean')); // true
print_r($storage->get('array')); // ['a' => 1, 'b' => 2]
var_dump($storage->get('null')); // null

// 4. Batch operations
$storage->setMultiple([
    'key1' => 'value1',
    'key2' => 'value2',
    'key3' => 'value3',
]);

$values = $storage->getMultiple(['key1', 'key2', 'key3']);
print_r($values);

// 5. Vérification d'existence
if ($storage->exists('key1')) {
    echo "key1 existe";
}

// 6. Changement de namespace
$storage->setNamespace('user_preferences');
$storage->set('theme', 'dark');

echo $storage->getNamespace(); // 'user_preferences'

// 7. Récupération de toutes les données
$allData = $storage->getAll();
print_r($allData);

// 8. Vérification si vide
if (!$storage->isEmpty()) {
    echo "Des données existent";
}

// 9. Suppression
$storage->delete('key1');
$storage->deleteMultiple(['key2', 'key3']);

// 10. Vérification de session
if ($storage->isSessionActive()) {
    echo "Session active";
}

// 11. Nettoyage complet
$storage->clear();
```

## Voir aussi

- `SessionStorageInterface` - Interface du storage session
- `StorageInterface` - Interface de base
- `StorageFactory` - Factory pour créer des storages
- `CookieStorage` - Stockage en cookies
- `MemoryStorage` - Stockage en mémoire
- `JsonlStorage` - Stockage JSONL
- `CacheStorage` - Stockage avec cache