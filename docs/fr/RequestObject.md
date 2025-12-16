# Documentation de RequestObject

## Vue d’ensemble

`RequestObject` est une classe singleton qui encapsule toutes les données de requête HTTP et fournit une interface unifiée pour y accéder. Elle extrait les données de plusieurs sources (GET, POST, corps JSON, en-têtes, fichiers, sessions) et les normalise. Cette classe sert de point central pour l’accès aux données de requête dans l’application.

Emplacement : `Kernel/RequestObject.php`
Espace de noms : `App\Kernel`
Patron : Singleton

## Définition de classe

```php
class RequestObject
```

## Propriétés

### Privées

- `$instance` (static, ?RequestObject) — Instance singleton (défaut null)
- `$method` (string) — Méthode HTTP (`$_SERVER['REQUEST_METHOD']`)
- `$datas` (array) — Données fusionnées (GET, POST, JSON)
- `$headers` (array) — En-têtes HTTP (`getallheaders()`)
- `$files` (array) — Fichiers traités en objets FileUpload
- `$server` (array) — Informations d’environnement (`$_SERVER`)
- `$sessions` (array) — Données de session (`$_SESSION`)

## Méthodes

### Constructeur

```php
public function __construct()
```

- Extrait la méthode HTTP
- Fusionne GET/POST/JSON
- Récupère les en-têtes
- Stocke `$_SERVER` et `$_SESSION`
- Convertit `$_FILES` en FileUpload

---

### Privées

#### `decodeJSON(string $json): array`
Décodage JSON sûr (retourne tableau ou tableau vide en cas d’erreur).

#### `convertFiles(): array`
Normalise `$_FILES` via `FileFormator::convert()`, instancie des `FileUpload` par champ.

#### `getDatas(): array`
Fusionne les données selon la méthode HTTP :

- GET: JSON + `$_GET`
- POST: `$_POST` + JSON + `$_GET`
- PUT/PATCH/DELETE: JSON + `$_GET`

Lit `php://input`, décode via `decodeJSON()` puis `array_merge()`.

#### `makeRoute(string $route): string`
Nettoie la route, extrait un ID numérique final et l’ajoute à `datas['id']`, retourne la route sans l’ID.

---

### Publiques

#### `getMethod(): string`
Retourne la méthode HTTP.

#### `getAllDatas(): array`
Retourne toutes les données fusionnées.

#### `isAuth(): bool`
Toujours `false` actuellement (TODO futur).

#### `getAuthUser(): ?array`
Extrait/parse l’en-tête Authorization. Retourne `[type, credentials]` ou `null`.

#### `setData(string $key, mixed $value): void`
Ajoute/modifie une valeur dans les données de requête.

#### `getFiles(): array`
Retourne tous les fichiers : `['field' => [FileUpload...]]`.

#### `getFile(string $key): ?array`
Retourne les fichiers d’un champ donné ou `null` s’il n’existe pas.

#### `getRequestInstance(): RequestObject` (static)
Retourne l’instance singleton (création paresseuse).

#### `getURI(): string`
Retourne la route nettoyée (sans slashes extrêmes ni ID numérique final). Effet de bord : renseigne `id` si présent dans l’URL.

#### `getSessionValue(string $name): mixed`
Lit une valeur de session (ou `null`).

#### `setSessionValue(string $name, mixed $value): void`
Définit une valeur dans la session (synchro interne et `$_SESSION`).

---

## Patron Singleton

Usage :
```php
$request = RequestObject::getRequestInstance();
```
Bénéfices :
- Source unique de vérité
- Cohérence d’accès
- Efficacité mémoire

---

## Flux de traitement

```
Requête HTTP
  → Constructeur
     - Méthode
     - Fusion GET/POST/JSON
     - En-têtes
     - Server/Session
     - Fichiers → FileUpload
  → Instance prête via getRequestInstance()
```

## Priorité de fusion (POST)

```
$_POST (priorité la plus haute)
JSON
$_GET (priorité la plus basse)
```

## Dépendances

- FileFormator (`App\Kernel\Files\FileFormator`)
- FileUpload (`App\Kernel\Files\FileUpload`)

Fonctions PHP : `getallheaders`, `json_decode`, `json_last_error`, `file_get_contents`, `parse_url`, `filter_var`, `explode`, `array_merge`.

---

## Exemples d’utilisation

### Base

```php
$request = RequestObject::getRequestInstance();
$method = $request->getMethod();
$data = $request->getAllDatas();
$id = $data['id'] ?? null;
```

### Méthodes HTTP

```php
switch ($request->getMethod()) {
    case 'GET': /* ... */ break;
    case 'POST': /* ... */ break;
    case 'PUT': /* ... */ break;
    case 'DELETE': /* ... */ break;
}
```

### Fichiers

```php
$all = $request->getFiles();
$avatars = $request->getFile('avatar');
if ($avatars) { foreach ($avatars as $file) { /* FileUpload */ } }
```

### Authentification

```php
$auth = $request->getAuthUser();
if ($auth && $auth[0] === 'Bearer') { $token = $auth[1]; }
```

### Session

```php
$userId = $request->getSessionValue('user_id');
$request->setSessionValue('user_id', 42);
```

### Ajout de données

```php
$request->setData('id', 123);
$request->setData('is_admin', true);
```

---

## Gestion des erreurs

- JSON invalide → tableau vide, pas d’exception
- Authorization manquant → `null`
- Champ fichier absent → `null`

## Bonnes pratiques

1. Utiliser le singleton (pas de `new RequestObject()`)
2. Valider les données (ex. `id` numérique)
3. Tester l’existence de fichiers avant traitement
4. Utiliser les types et gérer les valeurs manquantes

## Performance

- Initialisation paresseuse, extraction unique
- Conversion des fichiers en objets lors de l’initialisation

## Tests (exemples)

```php
class RequestObjectTest extends TestCase {
    public function testSingleton() {
        $r1 = RequestObject::getRequestInstance();
        $r2 = RequestObject::getRequestInstance();
        $this->assertSame($r1, $r2);
    }
}
```

## Classes liées

- RouterObject — utilise `getURI()` pour le routage
- AbstractController — consomme RequestObject
- FileUpload — représente un upload
- FileFormator — normalise `$_FILES`
- AuthBearerMiddleware — s’appuie sur RequestObject pour l’auth

## Journal

### Version 1.0
- Implémentation initiale : Singleton, fusion multi-sources, fichiers, sessions, Authorization

## Améliorations futures

- [ ] Gestion CSRF
- [ ] Gestion des cookies
