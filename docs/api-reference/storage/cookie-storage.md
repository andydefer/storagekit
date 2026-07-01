# CookieStorage - Référence Technique

## Description

CookieStorage est une implémentation de stockage utilisant les cookies HTTP. Les données sont stockées dans le navigateur de l'utilisateur et persistent entre les requêtes.

## Hiérarchie / Implémentations

```
CookieStorageInterface
    └── CookieStorage
```

**Interfaces implémentées :** `StorageInterface`, `CookieStorageInterface`

## Rôle principal

CookieStorage permet de stocker des données dans les cookies du navigateur. Il gère automatiquement l'encodage/décodage des valeurs complexes (tableaux, objets) et offre un contrôle fin sur les paramètres des cookies (expiration, domaine, chemin, sécurité). Idéal pour les préférences utilisateur, les sessions légères, et les données persistantes côté client.

## Installation

```bash
composer require andydefer/storage-kit
```

## API / Méthodes publiques

### `__construct(string $prefix = 'storage_', ?int $expires = null, ?string $domain = null, string $path = '/', bool $secure = false, bool $httpOnly = true, ?string $sameSite = 'Lax')`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$prefix` | `string` | Préfixe des noms de cookies (défaut: 'storage_') |
| `$expires` | `int|null` | Date d'expiration en timestamp Unix (défaut: 30 jours) |
| `$domain` | `string|null` | Domaine du cookie (défaut: null) |
| `$path` | `string` | Chemin du cookie (défaut: '/') |
| `$secure` | `bool` | Envoyer uniquement en HTTPS (défaut: false) |
| `$httpOnly` | `bool` | Rendre inaccessible à JavaScript (défaut: true) |
| `$sameSite` | `string|null` | Attribut SameSite (Lax, Strict, None) (défaut: 'Lax') |

**Retourne :** `void`

**Exemple :**
```php
$storage = new CookieStorage('app_', time() + 3600, '.example.com', '/', true, true, 'Strict');
```

---

### `getPrefix(): string`

Retourne le préfixe des cookies.

**Retourne :** `string` - Préfixe des cookies

**Exemple :**
```php
$prefix = $storage->getPrefix(); // 'storage_'
```

---

### `get(string $key, mixed $default = null): mixed`

Récupère une valeur depuis un cookie.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé du cookie (sans le préfixe) |
| `$default` | `mixed` | Valeur par défaut si le cookie n'existe pas |

**Retourne :** `mixed` - La valeur décodée ou la valeur par défaut

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
$values = $storage->getMultiple(['user_id', 'theme', 'language']);
```

---

### `set(string $key, mixed $value): void`

Stocke une valeur dans un cookie.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé du cookie (sans le préfixe) |
| `$value` | `mixed` | Valeur à stocker (sera sérialisée) |

**Retourne :** `void`

**Exemple :**
```php
$storage->set('user_id', 123);
$storage->set('preferences', ['theme' => 'dark', 'language' => 'fr']);
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
    'theme' => 'dark',
    'language' => 'fr',
]);
```

---

### `delete(string $key): bool`

Supprime un cookie.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé du cookie à supprimer |

**Retourne :** `bool` - `true` si le cookie existait, `false` sinon

**Exemple :**
```php
$storage->delete('session_token');
```

---

### `deleteMultiple(array $keys): void`

Supprime plusieurs cookies.

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

Vérifie si un cookie existe.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé du cookie à vérifier |

**Retourne :** `bool` - `true` si le cookie existe, `false` sinon

**Exemple :**
```php
if ($storage->exists('user_id')) {
    $userId = $storage->get('user_id');
}
```

---

### `clear(): void`

Supprime tous les cookies du préfixe.

**Retourne :** `void`

**Exemple :**
```php
$storage->clear(); // Supprime tous les cookies commençant par 'storage_'
```

---

### `setExpires(int|string $expires): self`

Définit la date d'expiration des cookies.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$expires` | `int|string` | Timestamp Unix ou chaîne relative ('+1 hour') |

**Retourne :** `self` - Interface fluide

**Exemple :**
```php
$storage->setExpires('+1 week'); // Expire dans 1 semaine
```

---

### `getExpires(): ?int`

Retourne la date d'expiration des cookies.

**Retourne :** `int|null` - Timestamp Unix ou null

**Exemple :**
```php
$expires = $storage->getExpires(); // 1735689600
```

---

### `setDomain(string $domain): self`

Définit le domaine des cookies.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$domain` | `string` | Domaine (ex: '.example.com') |

**Retourne :** `self` - Interface fluide

**Exemple :**
```php
$storage->setDomain('.example.com');
```

---

### `getDomain(): ?string`

Retourne le domaine des cookies.

**Retourne :** `string|null` - Domaine ou null

---

### `setPath(string $path): self`

Définit le chemin des cookies.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$path` | `string` | Chemin (ex: '/admin') |

**Retourne :** `self` - Interface fluide

**Exemple :**
```php
$storage->setPath('/admin');
```

---

### `getPath(): string`

Retourne le chemin des cookies.

**Retourne :** `string` - Chemin

---

### `setSecure(bool $secure = true): self`

Active ou désactive le flag Secure.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$secure` | `bool` | Envoyer uniquement en HTTPS |

**Retourne :** `self` - Interface fluide

**Exemple :**
```php
$storage->setSecure(true);
```

---

### `isSecure(): bool`

Vérifie si le flag Secure est activé.

**Retourne :** `bool`

---

### `setHttpOnly(bool $httpOnly = true): self`

Active ou désactive le flag HttpOnly.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$httpOnly` | `bool` | Rendre inaccessible à JavaScript |

**Retourne :** `self` - Interface fluide

**Exemple :**
```php
$storage->setHttpOnly(false); // Permet l'accès via JavaScript
```

---

### `isHttpOnly(): bool`

Vérifie si le flag HttpOnly est activé.

**Retourne :** `bool`

---

### `setSameSite(string $sameSite): self`

Définit l'attribut SameSite.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$sameSite` | `string` | 'Lax', 'Strict' ou 'None' |

**Retourne :** `self` - Interface fluide

**Exceptions :** `InvalidArgumentException` si la valeur n'est pas valide

**Exemple :**
```php
$storage->setSameSite('Strict');
```

---

### `getSameSite(): ?string`

Retourne l'attribut SameSite.

**Retourne :** `string|null` - 'Lax', 'Strict', 'None' ou null

---

### `getAll(): array`

Récupère toutes les données des cookies du préfixe.

**Retourne :** `array<string, mixed>` - Toutes les données

**Exemple :**
```php
$allData = $storage->getAll();
```

---

### `isEmpty(): bool`

Vérifie si le storage est vide.

**Retourne :** `bool`

**Exemple :**
```php
if ($storage->isEmpty()) {
    // Aucun cookie avec ce préfixe
}
```

---

### `setMultipleWithConfig(array $items): void`

Stocke plusieurs cookies avec la configuration actuelle.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$items` | `array<string, mixed>` | Tableau associatif clé → valeur |

**Retourne :** `void`

**Exemple :**
```php
$storage->setMultipleWithConfig([
    'session_id' => 'abc123',
    'user_role' => 'admin',
]);
```

## Cas d'utilisation

### Cas 1 : Préférences utilisateur

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
}

$prefs = new UserPreferences();
$prefs->setTheme('dark');
$prefs->setLanguage('fr');

echo $prefs->getTheme(); // 'dark'
echo $prefs->getLanguage(); // 'fr'
```

### Cas 2 : Panier d'achat

```php
class ShoppingCart
{
    private CookieStorage $storage;
    
    public function __construct()
    {
        $this->storage = new CookieStorage('cart_', '+7 days');
    }
    
    public function addItem(string $id, int $quantity): void
    {
        $cart = $this->storage->get('items', []);
        $cart[$id] = ($cart[$id] ?? 0) + $quantity;
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
    
    public function clear(): void
    {
        $this->storage->delete('items');
    }
}

$cart = new ShoppingCart();
$cart->addItem('p1', 2);
$cart->addItem('p2', 1);
$cart->addItem('p1', 1); // Ajoute 1 de plus

echo $cart->getTotalItems(); // 4
print_r($cart->getItems()); // ['p1' => 3, 'p2' => 1]
```

### Cas 3 : Session légère

```php
class LightSession
{
    private CookieStorage $storage;
    
    public function __construct(string $sessionId)
    {
        $this->storage = new CookieStorage('session_', '+1 hour');
        $this->storage->set('id', $sessionId);
    }
    
    public function set(string $key, mixed $value): void
    {
        $data = $this->storage->get('data', []);
        $data[$key] = $value;
        $this->storage->set('data', $data);
    }
    
    public function get(string $key, mixed $default = null): mixed
    {
        $data = $this->storage->get('data', []);
        return $data[$key] ?? $default;
    }
    
    public function getSessionId(): string
    {
        return $this->storage->get('id', '');
    }
    
    public function destroy(): void
    {
        $this->storage->delete('id');
        $this->storage->delete('data');
    }
}

$session = new LightSession('sess_123');
$session->set('user_id', 456);
$session->set('username', 'john_doe');

echo $session->get('username'); // 'john_doe'
echo $session->getSessionId(); // 'sess_123'
```

## Flux d'exécution

```
set($key, $value)
    ↓
buildCookieKey($key) = prefix . key
    ↓
encodeValue($value) = base64_encode(serialize($value))
    ↓
setCookie(key, encoded_value, options)
    ↓
$_COOKIE[key] = encoded_value
```

```
get($key, $default)
    ↓
buildCookieKey($key) = prefix . key
    ↓
isset($_COOKIE[key])?
    ├── Oui → decodeValue($value)
    │   ↓
    │   base64_decode()
    │   ↓
    │   unserialize()
    │   ↓
    │   return value
    └── Non → return $default
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| SameSite invalide | `InvalidArgumentException` | `SameSite must be one of: Lax, Strict, None` |
| Échec de setcookie | `RuntimeException` | `Failed to set cookie: {key}` |

## Intégration

### Avec StorageFactory

```php
use AndyDefer\StorageKit\Factory\StorageFactory;
use AndyDefer\StorageKit\Enums\StorageSystem;

$factory = new StorageFactory();
$storage = $factory->create(StorageSystem::COOKIE);
```

### Avec les structures AlgoKIT

```php
use AndyDefer\AlgoKIT\Algorithms\Trie;
use AndyDefer\StorageKit\Storage\CookieStorage;

$storage = new CookieStorage('user_');
$trie = new Trie($storage, 'autocomplete');
$trie->insert('laravel');
```

## Performance

| Opération | Complexité | Notes |
|-----------|------------|-------|
| `get()` | O(1) | Accès direct à $_COOKIE |
| `set()` | O(1) | Écriture via setcookie() |
| `getMultiple()` | O(n) | n = nombre de clés |
| `setMultiple()` | O(n) | n = nombre d'éléments |
| `getAll()` | O(n) | n = nombre de cookies |

**Limitations :**
- Taille maximale par cookie : ~4KB
- Nombre de cookies par domaine : ~50-150 (selon navigateur)
- Les données sont visibles par l'utilisateur

## Compatibilité

| Version | Support |
|---------|---------|
| PHP 8.1+ | ✅ Complet |
| PHP 8.0 | ✅ Complet |

## Exemple complet

```php
<?php

declare(strict_types=1);

use AndyDefer\StorageKit\Storage\CookieStorage;

// 1. Création
$storage = new CookieStorage(
    prefix: 'app_',
    expires: '+30 days',
    path: '/',
    secure: false,
    httpOnly: true,
    sameSite: 'Lax'
);

// 2. Stockage de différents types
$storage->set('string', 'Hello World');
$storage->set('integer', 42);
$storage->set('boolean', true);
$storage->set('array', ['a' => 1, 'b' => 2]);
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

// 6. Modification des paramètres
$storage->setSecure(true);
$storage->setSameSite('Strict');

// 7. Récupération de toutes les données
$allData = $storage->getAll();
print_r($allData);

// 8. Vérification si vide
if (!$storage->isEmpty()) {
    echo "Des cookies existent";
}

// 9. Suppression
$storage->delete('key1');
$storage->deleteMultiple(['key2', 'key3']);

// 10. Nettoyage complet
$storage->clear();
```

## Voir aussi

- `CookieStorageInterface` - Interface du storage cookie
- `StorageInterface` - Interface de base
- `StorageFactory` - Factory pour créer des storages
- `SessionStorage` - Stockage en session
- `MemoryStorage` - Stockage en mémoire
- `JsonlStorage` - Stockage JSONL
- `CacheStorage` - Stockage avec cache