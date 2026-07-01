# SqliteStorage - Référence Technique

## Description

SqliteStorage est un adaptateur de stockage utilisant SQLite comme base de données persistante pour les structures de données AlgoKIT. Il implémente l'interface `StorageInterface` en utilisant une base SQLite pour une persistance fiable avec support des transactions ACID.

## Hiérarchie / Implémentations

```
StorageInterface
    └── SqliteStorageInterface
            └── SqliteStorage (final)
```

La classe implémente l'interface `SqliteStorageInterface` et utilise :
- `SQLite3` pour la connexion à la base de données
- `SqliteStorageStatsRecord` pour les statistiques de stockage

## Rôle principal

SqliteStorage fournit une couche de persistance robuste pour les structures de données AlgoKIT en utilisant SQLite. Il offre :

- **Persistance ACID** : Les transactions garantissent l'intégrité des données
- **Support des transactions imbriquées** : Avec compteur de transactions
- **Opérations batch** : `setMultiple()`, `getMultiple()`, `deleteMultiple()`
- **Statistiques détaillées** : Nombre d'éléments, taille de la base, opérations
- **Optimisation** : Commande VACUUM pour défragmenter la base
- **Zéro configuration** : Création automatique de la table et des répertoires

## Installation

```bash
composer require andydefer/storage-kit
```

Prérequis :
- PHP 8.1 ou supérieur
- Extension `sqlite3` activée
- Permissions d'écriture sur le répertoire de la base de données

## API / Méthodes publiques

### `__construct(string $database = ':memory:', string $table = 'storage_kv')`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$database` | `string` | Chemin vers le fichier SQLite (':memory:' pour base en mémoire) |
| `$table` | `string` | Nom de la table pour le stockage clé-valeur |

**Retourne :** `void`

**Exceptions :** `RuntimeException` - Si le répertoire parent ne peut pas être créé

**Exemple :**
```php
$storage = new SqliteStorage('/var/data/storage.db', 'my_kv');
```

---

### `get(string $key, mixed $default = null): mixed`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | La clé de stockage |
| `$default` | `mixed` | Valeur par défaut si la clé n'existe pas |

**Retourne :** `mixed` - La valeur stockée ou la valeur par défaut

**Exemple :**
```php
$value = $storage->get('user_123', ['name' => 'Unknown']);
```

---

### `set(string $key, mixed $value): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | La clé de stockage |
| `$value` | `mixed` | La valeur à stocker (sérialisée automatiquement) |

**Retourne :** `void`

**Exemple :**
```php
$storage->set('user_123', ['name' => 'John', 'age' => 30]);
```

---

### `delete(string $key): bool`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | La clé à supprimer |

**Retourne :** `bool` - `true` si la clé existait et a été supprimée

**Exemple :**
```php
$deleted = $storage->delete('user_123');
```

---

### `exists(string $key): bool`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | La clé à vérifier |

**Retourne :** `bool` - `true` si la clé existe

**Exemple :**
```php
if ($storage->exists('user_123')) {
    $user = $storage->get('user_123');
}
```

---

### `getMultiple(array $keys): array`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$keys` | `string[]` | Liste des clés à récupérer |

**Retourne :** `array<string, mixed>` - Tableau associatif clé → valeur

**Exemple :**
```php
$users = $storage->getMultiple(['user_1', 'user_2', 'user_3']);
```

---

### `setMultiple(array $items): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$items` | `array<string, mixed>` | Tableau associatif clé → valeur |

**Retourne :** `void`

**Exceptions :** `Exception` - Si une erreur SQLite survient (rollback automatique)

**Exemple :**
```php
$storage->setMultiple([
    'user_1' => ['name' => 'Alice'],
    'user_2' => ['name' => 'Bob'],
]);
```

---

### `deleteMultiple(array $keys): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$keys` | `string[]` | Liste des clés à supprimer |

**Retourne :** `void`

**Exemple :**
```php
$storage->deleteMultiple(['user_1', 'user_2']);
```

---

### `clear(): void`

**Retourne :** `void`

**Exemple :**
```php
$storage->clear(); // Supprime toutes les données
```

---

### `beginTransaction(): bool`

Démarre une transaction SQLite. Supporte les transactions imbriquées.

**Retourne :** `bool` - `true` si la transaction a démarré

**Exemple :**
```php
$storage->beginTransaction();
$storage->set('key1', 'value1');
$storage->set('key2', 'value2');
$storage->commit();
```

---

### `commit(): bool`

Valide la transaction en cours. Pour les transactions imbriquées, seul le commit du niveau le plus externe persiste les données.

**Retourne :** `bool` - `true` si la transaction a été validée

**Exemple :**
```php
$storage->commit();
```

---

### `rollback(): bool`

Annule la transaction en cours. Annule toutes les modifications effectuées depuis le début de la transaction.

**Retourne :** `bool` - `true` si la transaction a été annulée

**Exemple :**
```php
$storage->rollback();
```

---

### `inTransaction(): bool`

Vérifie si une transaction est active.

**Retourne :** `bool` - `true` si une transaction est en cours

**Exemple :**
```php
if ($storage->inTransaction()) {
    $storage->commit();
}
```

---

### `count(): int`

Retourne le nombre d'éléments stockés dans la base.

**Retourne :** `int` - Nombre total de paires clé-valeur

**Exemple :**
```php
$total = $storage->count();
echo "Éléments stockés : $total\n";
```

---

### `getDatabaseSize(): int`

Retourne la taille du fichier de base de données en octets.

**Retourne :** `int` - Taille en octets (0 pour les bases en mémoire)

**Exemple :**
```php
$size = $storage->getDatabaseSize();
echo "Taille : " . round($size / 1024, 2) . " KB\n";
```

---

### `getStats(): SqliteStorageStatsRecord`

Retourne des statistiques détaillées sur le stockage.

**Retourne :** `SqliteStorageStatsRecord` - Record contenant les métriques

**Exemple :**
```php
$stats = $storage->getStats();
echo "Items: {$stats->total_items}\n";
echo "Taille: {$stats->database_size} octets\n";
echo "Écritures: {$stats->write_count}\n";
echo "Lectures: {$stats->read_count}\n";
```

---

### `vacuum(): bool`

Optimise la base de données en exécutant la commande VACUUM. Défragmente et récupère l'espace inutilisé.

**Retourne :** `bool` - `true` si VACUUM a réussi

**Exemple :**
```php
if ($storage->vacuum()) {
    echo "Base de données optimisée.\n";
}
```

---

### `getDatabasePath(): string`

Retourne le chemin du fichier de base de données.

**Retourne :** `string` - Chemin vers le fichier SQLite

**Exemple :**
```php
echo "Base : " . $storage->getDatabasePath();
```

---

### `getTableName(): string`

Retourne le nom de la table utilisée pour le stockage.

**Retourne :** `string` - Nom de la table

---

### `isMemoryDatabase(): bool`

Vérifie si la base est en mémoire (':memory:').

**Retourne :** `bool` - `true` si la base est en mémoire

---

### `getConnection(): SQLite3`

Retourne l'instance SQLite3 sous-jacente pour des opérations avancées.

**Retourne :** `SQLite3` - La connexion SQLite

**Exemple :**
```php
$db = $storage->getConnection();
// Opérations SQL avancées
$db->exec('CREATE INDEX ...');
```

---

### `close(): bool`

Ferme la connexion à la base de données.

**Retourne :** `bool` - `true` si la connexion a été fermée

**Exemple :**
```php
$storage->close();
```

---

## Cas d'utilisation

### Cas 1 : Stockage persistant pour un Trie d'autocomplétion

```php
<?php

declare(strict_types=1);

use AndyDefer\AlgoKIT\Algorithms\Trie;
use AndyDefer\StorageKit\Storage\SqliteStorage;

// Base de données persistante
$storage = new SqliteStorage('/var/data/autocomplete.db', 'trie_data');
$trie = new Trie($storage, 'search_terms');

// Indexation des termes
$terms = ['laravel', 'php', 'python', 'javascript', 'laragon'];
foreach ($terms as $term) {
    $trie->insert($term);
}

// Les données sont automatiquement persistées dans SQLite
echo "Termes indexés : " . $storage->count() . "\n";

// Redémarrage de l'application
$newStorage = new SqliteStorage('/var/data/autocomplete.db', 'trie_data');
$newTrie = new Trie($newStorage, 'search_terms');

// Données toujours disponibles
$results = $newTrie->search('la');
echo "Suggestions :\n";
foreach ($results as $result) {
    echo "  • {$result->word}\n";
}

$newStorage->close();
```

### Cas 2 : Analyse de logs avec transactions

```php
<?php

declare(strict_types=1);

use AndyDefer\AlgoKIT\Algorithms\CountMinSketch;
use AndyDefer\AlgoKIT\Algorithms\HyperLogLog;
use AndyDefer\StorageKit\Storage\SqliteStorage;

class LogAnalyzer
{
    private SqliteStorage $storage;
    private CountMinSketch $cms;
    private HyperLogLog $hll;
    
    public function __construct(string $dbPath)
    {
        $this->storage = new SqliteStorage($dbPath, 'log_analytics');
        $this->cms = new CountMinSketch($this->storage, 10000, 5, 'frequencies');
        $this->hll = new HyperLogLog($this->storage, 14, 'uniques');
    }
    
    public function processLogs(array $logs): void
    {
        // Transaction pour atomicité
        $this->storage->beginTransaction();
        
        try {
            foreach ($logs as $log) {
                $this->cms->add($log['endpoint']);
                $this->hll->add($log['ip'], date('Y-m-d'));
            }
            $this->storage->commit();
            echo "✅ Logs traités avec succès\n";
        } catch (\Exception $e) {
            $this->storage->rollback();
            echo "❌ Erreur, transaction annulée : " . $e->getMessage() . "\n";
        }
    }
    
    public function getStats(): array
    {
        $stats = $this->storage->getStats();
        return [
            'total_items' => $stats->total_items,
            'database_size' => round($stats->database_size / 1024, 2) . ' KB',
            'write_count' => $stats->write_count,
            'read_count' => $stats->read_count,
        ];
    }
    
    public function close(): void
    {
        $this->storage->close();
    }
}

// Utilisation
$analyzer = new LogAnalyzer('/var/data/log_analytics.db');
$analyzer->processLogs([
    ['endpoint' => '/api/users', 'ip' => '192.168.1.1'],
    ['endpoint' => '/api/products', 'ip' => '192.168.1.2'],
    ['endpoint' => '/api/users', 'ip' => '192.168.1.1'],
]);

print_r($analyzer->getStats());
$analyzer->close();
```

### Cas 3 : Stockage de configuration d'application

```php
<?php

declare(strict_types=1);

use AndyDefer\StorageKit\Storage\SqliteStorage;

class AppConfig
{
    private SqliteStorage $storage;
    
    public function __construct(string $dbPath)
    {
        $this->storage = new SqliteStorage($dbPath, 'app_config');
    }
    
    public function setConfig(string $key, $value): void
    {
        $this->storage->set("config:{$key}", $value);
    }
    
    public function getConfig(string $key, $default = null)
    {
        return $this->storage->get("config:{$key}", $default);
    }
    
    public function setUserPreference(string $userId, string $pref, $value): void
    {
        $this->storage->set("user:{$userId}:{$pref}", $value);
    }
    
    public function getUserPreferences(string $userId): array
    {
        // Récupérer toutes les préférences d'un utilisateur
        $keys = [];
        // ... logique de scan (à implémenter)
        return $this->storage->getMultiple($keys);
    }
    
    public function getStats(): SqliteStorageStatsRecord
    {
        return $this->storage->getStats();
    }
    
    public function optimize(): void
    {
        $this->storage->vacuum();
    }
    
    public function close(): void
    {
        $this->storage->close();
    }
}

// Utilisation
$config = new AppConfig('/var/data/app_config.db');

// Configuration générale
$config->setConfig('app_name', 'Mon Application');
$config->setConfig('version', '1.2.3');
$config->setConfig('debug', true);

// Préférences utilisateur
$config->setUserPreference('user_123', 'theme', 'dark');
$config->setUserPreference('user_123', 'language', 'fr');

// Lecture
echo "App : " . $config->getConfig('app_name') . "\n";
echo "Theme : " . $config->getUserPreference('user_123', 'theme') . "\n";
echo "Debug : " . ($config->getConfig('debug') ? 'ON' : 'OFF') . "\n";

// Statistiques
$stats = $config->getStats();
echo "Éléments : {$stats->total_items}\n";
echo "Taille : " . round($stats->database_size / 1024, 2) . " KB\n";

$config->close();
```

## Flux d'exécution

### Opération get()

```
get($key, $default)
    ↓
readCount++
    ↓
Prepare SQL : SELECT value FROM table WHERE key = :key
    ↓
Execute query
    ↓
Result found ?
    ├── OUI → unserialize(value) → return value
    └── NON → return $default
```

### Opération set() avec transaction

```
set($key, $value)
    ↓
writeCount++
    ↓
serialize($value)
    ↓
Prepare SQL : INSERT OR REPLACE INTO table ...
    ↓
Execute query
    ↓
Transaction active ?
    ├── OUI → Changes pending in transaction
    └── NON → Immediately persisted
```

### Transaction imbriquée

```
beginTransaction() → transactionCount = 1
    ├── beginTransaction() → transactionCount = 2
    ├── commit() → transactionCount = 1
    └── commit() → transactionCount = 0 → COMMIT SQL
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Répertoire parent inexistant | `RuntimeException` | `Unable to create directory "{path}" for SQLite database` |
| Erreur SQLite pendant setMultiple | `Exception` | Message de l'erreur SQLite |
| Erreur SQLite pendant getMultiple | `Exception` | Message de l'erreur SQLite |
| Connexion SQLite impossible | `Exception` | `Unable to open database: {message}` |

## Intégration

### Avec AlgoKIT

```php
use AndyDefer\AlgoKIT\Algorithms\Trie;
use AndyDefer\AlgoKIT\Algorithms\BloomFilter;
use AndyDefer\AlgoKIT\Algorithms\CountMinSketch;
use AndyDefer\AlgoKIT\Algorithms\HyperLogLog;
use AndyDefer\AlgoKIT\Algorithms\TopK;
use AndyDefer\AlgoKIT\Algorithms\BKTree;
use AndyDefer\StorageKit\Storage\SqliteStorage;

$storage = new SqliteStorage('/path/to/algo_data.db', 'storage_kv');

$trie = new Trie($storage, 'autocomplete');
$bloom = new BloomFilter($storage, 100000, 3, 'bloom');
$cms = new CountMinSketch($storage, 10000, 5, 'cms');
$hll = new HyperLogLog($storage, 14, 'hll');
$topK = new TopK($storage, 10, 'topk');
$bkTree = new BKTree($storage, 'bktree');

// Toutes les structures partagent la même base SQLite
```

### Avec StorageFactory

```php
use AndyDefer\StorageKit\Factory\StorageFactory;
use AndyDefer\StorageKit\Enums\StorageSystem;

$factory = new StorageFactory('/var/data', 3600, 2);
$storage = $factory->createSqliteStorage('/var/data/app.db', 'my_table');
// ou
$storage = $factory->createPersistentSqliteStorage('app.db', 'my_table');
```

## Performance

| Opération | Complexité | Description |
|-----------|------------|-------------|
| `get()` | O(1) | Recherche par clé primaire indexée |
| `set()` | O(1) | Insertion avec remplacement (clé primaire) |
| `delete()` | O(1) | Suppression par clé primaire |
| `getMultiple()` | O(n) | n = nombre de clés |
| `setMultiple()` | O(n) | n = nombre de clés, avec transaction |
| `count()` | O(1) | Utilise COUNT(*) avec cache du moteur |

**Optimisations :**
- Clé primaire sur la colonne `key` pour des recherches rapides
- Transactions pour les opérations batch
- Sérialisation PHP optimisée
- Mode WAL recommandé pour les performances

**Recommandation :** Activer le mode WAL pour de meilleures performances en écriture :

```php
$storage = new SqliteStorage('data.db');
$storage->getConnection()->exec('PRAGMA journal_mode=WAL');
```

## Compatibilité

| Version | Support | Notes |
|---------|---------|-------|
| PHP 8.1+ | ✅ Complet | Types et syntaxe recommandés |
| PHP 8.0 | ✅ Complet | Compatible avec ajustements mineurs |
| PHP 7.4 | ❌ Non supporté | Utilise `fn()` et `readonly` |

## Exemple complet

```php
<?php

declare(strict_types=1);

use AndyDefer\AlgoKIT\Algorithms\Trie;
use AndyDefer\AlgoKIT\Algorithms\CountMinSketch;
use AndyDefer\AlgoKIT\Algorithms\HyperLogLog;
use AndyDefer\StorageKit\Storage\SqliteStorage;

echo "🗄️ DÉMONSTRATION SQLITE STORAGE\n";
echo "═══════════════════════════════════\n\n";

// 1. Initialisation
$dbPath = sys_get_temp_dir() . '/algo_demo.db';
$storage = new SqliteStorage($dbPath, 'demo_kv');

echo "📁 Base : {$dbPath}\n";
echo "📋 Table : {$storage->getTableName()}\n";
echo "🧠 Mémoire : " . ($storage->isMemoryDatabase() ? 'Oui' : 'Non') . "\n\n";

// 2. Stockage de données
echo "📝 Stockage des données :\n";
$data = [
    ['key' => 'user_1', 'value' => ['name' => 'Alice', 'age' => 25]],
    ['key' => 'user_2', 'value' => ['name' => 'Bob', 'age' => 30]],
    ['key' => 'user_3', 'value' => ['name' => 'Charlie', 'age' => 35]],
];

foreach ($data as $item) {
    $storage->set($item['key'], $item['value']);
    echo "  ✓ {$item['key']}\n";
}

// 3. Opérations batch
echo "\n📦 Opérations batch :\n";
$batch = [
    'config:app' => 'Mon App',
    'config:version' => '1.0.0',
    'config:debug' => true,
];
$storage->setMultiple($batch);
echo "  ✓ " . count($batch) . " configurations ajoutées\n";

// 4. Lecture des données
echo "\n🔍 Lecture :\n";
$user = $storage->get('user_1');
echo "  user_1 : " . print_r($user, true);

$configs = $storage->getMultiple(['config:app', 'config:version']);
echo "  Config : " . print_r($configs, true);

// 5. Utilisation avec AlgoKIT
echo "\n🧮 Utilisation avec AlgoKIT :\n";
$cms = new CountMinSketch($storage, 1000, 3, 'demo_cms');
$hll = new HyperLogLog($storage, 10, 'demo_hll');
$trie = new Trie($storage, 'demo_trie');

$cms->add('php');
$cms->add('php');
$cms->add('laravel');
$hll->add('user_1');
$hll->add('user_2');
$hll->add('user_1');
$trie->insert('laravel');
$trie->insert('laragon');

echo "  CMS php : " . $cms->count('php') . "\n";
echo "  HLL total : " . $hll->count() . "\n";
echo "  Trie 'la' : " . implode(', ', array_map(fn($r) => $r->word, $trie->search('la')->toArray())) . "\n";

// 6. Statistiques
echo "\n📊 Statistiques :\n";
$stats = $storage->getStats();
echo "  Éléments : {$stats->total_items}\n";
echo "  Taille : " . round($stats->database_size / 1024, 2) . " KB\n";
echo "  Écritures : {$stats->write_count}\n";
echo "  Lectures : {$stats->read_count}\n";
echo "  Pages : {$stats->total_pages}\n";

// 7. Transaction
echo "\n🔄 Transaction :\n";
$storage->beginTransaction();
$storage->set('temp_1', 'valeur_1');
$storage->set('temp_2', 'valeur_2');
echo "  ✓ 2 éléments ajoutés en transaction\n";
$storage->commit();
echo "  ✅ Transaction validée\n";

// 8. Nettoyage
echo "\n🧹 Nettoyage :\n";
$storage->clear();
echo "  ✓ Données supprimées\n";

$storage->vacuum();
echo "  ✓ Base optimisée\n";

$storage->close();
echo "  ✓ Connexion fermée\n";

// Nettoyage du fichier
if (file_exists($dbPath)) {
    unlink($dbPath);
    echo "  ✓ Fichier supprimé\n";
}
```

## Voir aussi

- [`cache-storage`](cache-storage.md) - Stockage cache
- [`cookie-storage`](cookie-storage.md) - Stockage cookie
- [`jsonl-storage`](jsonl-storage.md) - Stockage jsonl
- [`memory-storage`](memory-storage.md) - Stockage mémoire
- [`session-storage`](session-storage.md) - Stockage session