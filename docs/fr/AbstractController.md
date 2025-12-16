# Documentation d’AbstractController

## Vue d’ensemble

`AbstractController` est une classe de base abstraite qui fournit les fondations de tous les contrôleurs du framework SmallMVC. Elle encapsule les fonctionnalités communes des contrôleurs, notamment la gestion de la requête, l’authentification, les réponses d’erreur et l’intégration de middleware. Tous les contrôleurs applicatifs doivent étendre cette classe pour hériter de ces capacités.

Emplacement : `Kernel/AbstractController.php`
Espace de noms : `App\Kernel`
Patrons : Classe de base abstraite, Template Method Pattern

## Définition de classe

```php
abstract class AbstractController
```

## Propriétés

### Propriétés protégées

#### `$connector` (ConnectorInterface)
- Type : `ConnectorInterface`
- Description : Connecteur base de données pour effectuer des opérations
- Portée : Protégée
- Initialisation : Par les sous-classes (pas dans AbstractController)
- Objectif : Fournir l’accès BD aux modèles
- Utilisation : Passé aux modèles pour les opérations de données

Exemple :
```php
public function __construct(AuthenticationInterface $authMiddleware)
{
    parent::__construct($authMiddleware);
    $this->connector = new Connector();
}
```

#### `$request` (RequestObject)
- Type : `RequestObject`
- Description : Instance singleton de RequestObject contenant toutes les données HTTP
- Portée : Protégée
- Initialisation : Dans le constructeur d’AbstractController
- Objectif : Accéder aux données (paramètres, en-têtes, fichiers, sessions)
- Utilisation : `$this->request->getAllDatas()`, `$this->request->getMethod()`, etc.

#### `$authMiddleware` (AuthenticationInterface)
- Type : `AuthenticationInterface`
- Description : Middleware d’authentification pour gérer la logique d’auth
- Portée : Protégée (promotion de propriété)
- Initialisation : Via le paramètre du constructeur
- Objectif : Effectuer les vérifications d’authentification
- Utilisation : Injecté par RouterObject

## Méthodes

### Constructeur

```php
public function __construct(protected AuthenticationInterface $authMiddleware)
```

Description :
Initialise l’AbstractController avec le middleware d’authentification et l’objet requête.

Paramètres :
- `$authMiddleware` (AuthenticationInterface) - Instance de middleware d’authentification (propriété promue)

Type de retour : void

Comportement :
- Stocke le middleware comme propriété protégée
- Récupère l’instance singleton RequestObject
- Rend les données de requête disponibles pour toutes les méthodes

Promotion de propriété :
- Utilise la promotion de propriété du constructeur (PHP 8)
- `$authMiddleware` devient automatiquement une propriété protégée

Exemple :
```php
public function __construct(AuthenticationInterface $authMiddleware)
{
    parent::__construct($authMiddleware);
    $this->connector = new Connector();
}
```

Flux d’initialisation :
```
RouterObject instancie le contrôleur
    ↓
Passe AuthBearerMiddleware au constructeur
    ↓
Constructeur d’AbstractController appelé
    ├─ Stocke $authMiddleware
    └─ Obtient le singleton RequestObject
    ↓
Constructeur de la sous-classe appelé
    ├─ Appelle parent::__construct()
    └─ Initialise des propriétés supplémentaires
```

---

### Méthodes protégées

#### `returnError(int $error): ResponseInterface`

```php
protected function returnError(int $error): ResponseInterface
```

Description :
Crée et retourne une ClientErrorResponse avec le code d’erreur HTTP fourni.

Paramètres :
- `$error` (int) - Code statut HTTP d’erreur

Type de retour : `ResponseInterface`

Valeurs de retour :
- Objet ClientErrorResponse avec le code indiqué

Comportement :
- Crée une nouvelle instance de ClientErrorResponse
- Définit le code d’erreur
- Retourne l’objet réponse
- N’envoie pas la réponse (permet des modifications ultérieures)

Codes d’erreur courants :

| Code | Signification | Cas d’usage |
|------|----------------|-------------|
| 400 | Bad Request | Requête invalide |
| 401 | Unauthorized | Authentification manquante/invalide |
| 403 | Forbidden | Authentifié mais non autorisé |
| 404 | Not Found | Ressource inexistante |
| 405 | Method Not Allowed | Méthode HTTP non supportée |
| 422 | Unprocessable Entity | Erreur de validation |
| 429 | Too Many Requests | Limite de requêtes atteinte |
| 500 | Internal Server Error | Erreur serveur |

Exemple :
```php
public function index(): ResponseInterface
{
    if (!$this->isUserAuth()) {
        return $this->returnError(401);
    }
    
    $data = $this->request->getAllDatas();
    if (empty($data)) {
        return $this->returnError(400);
    }
    
    // Suite de la logique métier
}
```

---

#### `isUserAuth(): bool`

```php
protected function isUserAuth(): bool
```

Description :
Vérifie si la requête actuelle est authentifiée via un jeton Bearer.

Paramètres : Aucun

Type de retour : `bool`

Valeurs de retour :
- `true` si la requête possède un Bearer token valide
- `false` si l’authentification échoue ou le jeton est absent

Comportement :

1. Extraction de l’en-tête Authorization :
   - Appelle `$this->request->getAuthUser()`
   - Retourne `false` si l’en-tête est manquant

2. Validation du type d’authentification :
   - Vérifie que le type est « Bearer »
   - Retourne `false` sinon

3. Validation du jeton :
   - Crée une instance d’AuthBearerMiddleware
   - Appelle `isAuth()` avec le jeton
   - Retourne le résultat de validation

Flux d’authentification :
```
isUserAuth() appelé
    ↓
Récupère Authorization
    ├─ NULL → false
    └─ Existe → Continuer
    ↓
Vérifie le type
    ├─ ≠ "Bearer" → false
    └─ "Bearer" → Continuer
    ↓
Valide le jeton via le middleware
    ├─ Valide → true
    └─ Invalide → false
```

Exemple :
```php
public function index(): ResponseInterface
{
    if (!$this->isUserAuth()) {
        return $this->returnError(401);
    }
    
    // Utilisateur authentifié, poursuivre
    $data = $this->request->getAllDatas();
    // ...
}
```

Format de l’en-tête Authorization :
```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

Extraction :
- Type : `Bearer`
- Jeton : chaîne JWT

---

## Schémas d’utilisation

### Créer un contrôleur concret

```php
namespace App\Controllers;

use App\Kernel\AbstractController;
use App\Kernel\Interfaces\ResponseInterface;
use App\Services\Responses\JsonResponse;
use App\Interfaces\AuthenticationInterface;
use App\Services\Connector;

class UserController extends AbstractController
{
    public function __construct(AuthenticationInterface $authMiddleware)
    {
        parent::__construct($authMiddleware);
        $this->connector = new Connector();
    }

    public function index(): ResponseInterface
    {
        // Vérifier l’authentification
        if (!$this->isUserAuth()) {
            return $this->returnError(401);
        }

        // Données de requête
        $data = $this->request->getAllDatas();

        // Logique métier
        $response = new JsonResponse();
        $response->setBody(['users' => []]);
        return $response;
    }
}
```

### Gérer différentes méthodes HTTP

```php
public function index(): ResponseInterface
{
    switch ($this->request->getMethod()) {
        case 'GET':
            return $this->handleGet();
        case 'POST':
            return $this->handlePost();
        case 'PUT':
            return $this->handlePut();
        case 'DELETE':
            return $this->handleDelete();
        default:
            return $this->returnError(405);  // Method Not Allowed
    }
}

private function handleGet(): ResponseInterface
{
    // Logique GET
}

private function handlePost(): ResponseInterface
{
    // Logique POST
}
```

### Accéder aux données de requête

```php
public function index(): ResponseInterface
{
    // Toutes les données
    $data = $this->request->getAllDatas();

    // Valeurs spécifiques
    $id = $data['id'] ?? null;
    $name = $data['name'] ?? null;

    // Méthode HTTP
    $method = $this->request->getMethod();

    // Fichiers
    $files = $this->request->getFile('upload');

    // Session
    $userId = $this->request->getSessionValue('user_id');
}
```

### Utiliser le Connector

```php
public function index(): ResponseInterface
{
    $model = new UserModel($this->connector->getConnection());
    $users = $model->getAll();

    $response = new JsonResponse();
    $response->setBody($users);
    return $response;
}
```

### Gestion des erreurs

```php
public function index(): ResponseInterface
{
    // Valider les données
    $data = $this->request->getAllDatas();
    
    if (empty($data['id'])) {
        return $this->returnError(400);  // Bad Request
    }

    if (!is_numeric($data['id'])) {
        return $this->returnError(422);  // Unprocessable Entity
    }

    // Vérifier l’authentification
    if (!$this->isUserAuth()) {
        return $this->returnError(401);  // Unauthorized
    }

    // Suite de la logique métier
}
```

## Schémas courants de contrôleurs

### Contrôleur RESTful CRUD

```php
class ResourceController extends AbstractController
{
    public function __construct(AuthenticationInterface $authMiddleware)
    {
        parent::__construct($authMiddleware);
        $this->connector = new Connector();
    }

    public function index(): ResponseInterface
    {
        switch ($this->request->getMethod()) {
            case 'GET':
                return $this->getResources();
            case 'POST':
                return $this->createResource();
            case 'PUT':
                return $this->updateResource();
            case 'DELETE':
                return $this->deleteResource();
            default:
                return $this->returnError(405);
        }
    }

    private function getResources(): ResponseInterface
    {
        $data = $this->request->getAllDatas();
        $model = new ResourceModel($this->connector->getConnection());
        
        if (isset($data['id'])) {
            $resource = $model->getOne($data['id']);
            if (!$resource) {
                return $this->returnError(404);
            }
        } else {
            $resource = $model->getAll();
        }

        $response = new JsonResponse();
        $response->setBody($resource);
        return $response;
    }

    private function createResource(): ResponseInterface
    {
        if (!$this->isUserAuth()) {
            return $this->returnError(401);
        }

        $data = $this->request->getAllDatas();
        $model = new ResourceModel($this->connector->getConnection());
        $model->add($data);

        $response = new JsonResponse();
        $response->setStatusCode(201);
        $response->setBody(['id' => $model->lastId()]);
        return $response;
    }

    private function updateResource(): ResponseInterface
    {
        if (!$this->isUserAuth()) {
            return $this->returnError(401);
        }

        $data = $this->request->getAllDatas();
        if (!isset($data['id'])) {
            return $this->returnError(400);
        }

        $model = new ResourceModel($this->connector->getConnection());
        $model->update($data['id'], $data);

        $response = new JsonResponse();
        return $response;
    }

    private function deleteResource(): ResponseInterface
    {
        if (!$this->isUserAuth()) {
            return $this->returnError(401);
        }

        $data = $this->request->getAllDatas();
        if (!isset($data['id'])) {
            return $this->returnError(400);
        }

        $model = new ResourceModel($this->connector->getConnection());
        $model->delete($data['id']);

        $response = new JsonResponse();
        return $response;
    }
}
```

### Contrôleur authentifié

### Contrôleur d’upload de fichier

```php
class FileController extends AbstractController
{
    public function __construct(AuthenticationInterface $authMiddleware)
    {
        parent::__construct($authMiddleware);
        $this->connector = new Connector();
    }

    public function index(): ResponseInterface
    {
        if (!$this->isUserAuth()) {
            return $this->returnError(401);
        }

        $files = $this->request->getFile('upload');
        
        if (!$files) {
            return $this->returnError(400);
        }

        foreach ($files as $file) {
            // Traitement des fichiers
        }

        $response = new JsonResponse();
        $response->setStatusCode(201);
        return $response;
    }
}
```

## Bonnes pratiques

### 1. Toujours appeler le constructeur parent

```php
// Correct
public function __construct(AuthenticationInterface $authMiddleware)
{
    parent::__construct($authMiddleware);
    // Initialisations supplémentaires
}

// À éviter
public function __construct(AuthenticationInterface $authMiddleware)
{
    // parent::__construct() manquant
}
```

### 2. Vérifier l’authentification tôt

```php
// Correct
public function index(): ResponseInterface
{
    if (!$this->isUserAuth()) {
        return $this->returnError(401);
    }
    // Logique métier
}

// À éviter
public function index(): ResponseInterface
{
    // Logique métier
    if (!$this->isUserAuth()) {
        return $this->returnError(401);
    }
}
```

### 3. Valider les données de requête

```php
// Correct
$data = $this->request->getAllDatas();
$id = $data['id'] ?? null;

if ($id === null || !is_numeric($id)) {
    return $this->returnError(400);
}

// À éviter
$id = $data['id'];  // Peut ne pas exister
```

### 4. Utiliser les bons codes d’erreur

```php
// Correct
if (!$this->isUserAuth()) {
    return $this->returnError(401);  // Unauthorized
}

if (!$resource) {
    return $this->returnError(404);  // Not Found
}

// À éviter
return $this->returnError(500);  // Mauvais code d’erreur
```

### 5. Retourner ResponseInterface

```php
// Correct
public function index(): ResponseInterface
{
    return new JsonResponse();
}

// À éviter
public function index()
{
    $response = new JsonResponse();
    $response->send();  // Ne pas envoyer dans le contrôleur
}
```

### 6. Séparer les responsabilités

```php
// Correct
public function index(): ResponseInterface
{
    switch ($this->request->getMethod()) {
        case 'GET':
            return $this->handleGet();
        case 'POST':
            return $this->handlePost();
    }
}

private function handleGet(): ResponseInterface
{
    // Logique GET
}

// À éviter
public function index(): ResponseInterface
{
    // Toute la logique dans une seule méthode
}
```

## Dépendances

### Dépendances internes

- RequestObject (`App\Kernel\RequestObject`)
  - Accès aux données HTTP
  - Singleton

- AuthenticationInterface (`App\Interfaces\AuthenticationInterface`)
  - Interface du middleware d’authentification
  - Injectée via le constructeur

- AuthBearerMiddleware (`App\Middleware\AuthBearerMiddleware`)
  - Valide les jetons Bearer
  - Utilisé par `isUserAuth()`

- ResponseInterface (`App\Kernel\Interfaces\ResponseInterface`)
  - Interface des objets réponse
  - Type de retour des méthodes des contrôleurs

- ClientErrorResponse (`App\Services\Responses\ClientErrorResponse`)
  - Utilisée par `returnError()`

- ConnectorInterface (`App\Interfaces\ConnectorInterface`)
  - Interface du connecteur base de données
  - Initialisé par les sous-classes

## Accès aux données de requête

### Récupérer toutes les données

```php
$data = $this->request->getAllDatas();
// Exemple : ['id' => 1, 'name' => 'John', ...]
```

### Récupérer la méthode HTTP

```php
$method = $this->request->getMethod();
// 'GET', 'POST', 'PUT', 'DELETE', etc.
```

### Récupérer des fichiers

```php
$files = $this->request->getFile('upload');
// Retour : [FileUpload, FileUpload, ...] ou null
```

### Récupérer des données de session

```php
$userId = $this->request->getSessionValue('user_id');
// Valeur de session ou null
```

### Récupérer l’en-tête Authorization

```php
$auth = $this->request->getAuthUser();
// ['Bearer', 'token...'] ou null
```

## Flux d’authentification

### Authentification Bearer token

```
Requête avec en-tête Authorization
    ↓
Authorization: Bearer <token>
    ↓
isUserAuth() appelé
    ↓
Extraction : ['Bearer', '<token>']
    ↓
Type = 'Bearer' ✓
    ↓
Validation avec le middleware
    ├─ VALIDE → true
    └─ INVALIDE → false
    ↓
Le contrôleur poursuit ou retourne 401
```

## Réponses d’erreur

### 400 Bad Request

```php
if (empty($data['required_field'])) {
    return $this->returnError(400);
}
```

### 401 Unauthorized

```php
if (!$this->isUserAuth()) {
    return $this->returnError(401);
}
```

### 404 Not Found

```php
if (!$resource) {
    return $this->returnError(404);
}
```

### 405 Method Not Allowed

```php
default:
    return $this->returnError(405);
```

### 422 Unprocessable Entity

```php
if (!is_numeric($id)) {
    return $this->returnError(422);
}
```

## Tests

### Exemple de test unitaire

```php
use PHPUnit\Framework\TestCase;
use App\Controllers\UserController;
use App\Middleware\AuthBearerMiddleware;

class UserControllerTest extends TestCase
{
    private $controller;
    private $authMiddleware;

    protected function setUp(): void
    {
        $this->authMiddleware = new AuthBearerMiddleware();
        $this->controller = new UserController($this->authMiddleware);
    }

    public function testIndexRequiresAuthentication()
    {
        $response = $this->controller->index();
        $this->assertEquals(401, $response->statusCode);
    }

    public function testReturnError()
    {
        $response = $this->controller->returnError(404);
        $this->assertInstanceOf(ClientErrorResponse::class, $response);
    }
}
```

## Classes liées

- RouterObject (`App\Kernel\RouterObject`)
  - Instancie les contrôleurs et appelle les méthodes

- RequestObject (`App\Kernel\RequestObject`)
  - Fournit les données de requête

- AbstractResponse (`App\Kernel\AbstractResponse`)
  - Classe de base pour les réponses

- AuthBearerMiddleware (`App\Middleware\AuthBearerMiddleware`)
  - Valide les jetons Bearer

- ConnectorInterface (`App\Interfaces\ConnectorInterface`)
  - Interface de connexion BD

## Documentation liée

- [Documentation de RouterObject](./RouterObject.md) - Instanciation et appel des contrôleurs
- [Documentation de RequestObject](./RequestObject.md) - Accès aux données de requête
- [Documentation d’AbstractResponse](./AbstractResponse.md) - Création de réponses

## Journal des modifications

### Version 1.0
- Implémentation initiale
- Classe de base abstraite pour les contrôleurs
- Support de l’authentification
- Gestion des erreurs
- Accès aux données de requête

## Améliorations futures

- [ ] Contrôles d’autorisation (au-delà de l’authentification)
- [ ] Helpers de validation de requêtes
