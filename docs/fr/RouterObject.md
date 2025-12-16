# Documentation de RouterObject

## Vue d’ensemble

`RouterObject` est le composant de routage central du framework SmallMVC. Il gère le routage HTTP en faisant correspondre les chemins d’URI aux contrôleurs et méthodes correspondants. Cette classe agit comme dispatcheur central qui traite les requêtes entrantes et les dirige vers l’action de contrôleur appropriée.

Emplacement : `Kernel/RouterObject.php`
Espace de noms : `App\Kernel`

## Définition de classe

```php
class RouterObject
```

## Propriétés

### Privées

#### `$routeCall` (string)
- Chemin d’URI extrait de la requête courante
- Initialisé dans le constructeur via `RequestObject::getURI()`

#### `$request` (RequestObject)
- Instance singleton de RequestObject contenant les données HTTP
- Initialisée dans le constructeur via `RequestObject::getRequestInstance()`

#### `$routes` (array)
- Tableau associatif mappant les URI vers `[Controller, méthode]`
- Routes par défaut :
```php
[
    '' => ['\\App\\Controllers\\HomeController', 'index'],
    'items' => ['\\App\\Controllers\\ItemController', 'index'],
    'categories' => ['\\App\\Controllers\\CategoryController', 'index'],
]
```

## Méthodes

### Constructeur

```php
public function __construct()
```

- Récupère l’instance RequestObject
- Extrait la route via `getURI()`
- Stocke la valeur dans `$routeCall`

---

### `route()`

```php
public function route(): ResponseInterface
```

Méthode principale de routage qui traite la requête et la dispatche vers le contrôleur/méthode approprié. Retourne un objet `ResponseInterface`.

Valeurs de retour :
- Succès : l’objet réponse renvoyé par le contrôleur
- Route inconnue : `ClientErrorResponse(404)`
- Exception pendant l’exécution : `ErrorResponse(500)`

Comportement :
1. Vérifie l’existence de la route dans `$routes`
2. Récupère la config (classe contrôleur, méthode)
3. Instancie `AuthBearerMiddleware`
4. Instancie le contrôleur avec le middleware
5. Appelle la méthode spécifiée
6. Capture les exceptions → 500

Exemple :
```php
$router = new RouterObject();
$response = $router->route();
$response->send();
```

Schéma :
```
route()
  → clé présente ? non → 404
                     oui → (Classe, méthode)
  → new AuthBearerMiddleware
  → new Controller($middleware)
  → Controller::method()
       succès → ResponseInterface
       exception → ErrorResponse(500)
```

## Configuration des routes

Ajouter une route :
```php
private array $routes = [
    '' => ['\\App\\Controllers\\HomeController', 'index'],
    'items' => ['\\App\\Controllers\\ItemController', 'index'],
    'categories' => ['\\App\\Controllers\\CategoryController', 'index'],
    'users' => ['\\App\\Controllers\\UserController', 'index'],
    'products' => ['\\App\\Controllers\\ProductController', 'index'],
];
```

Format :
- Clé : chemin d’URI (sans slash initial)
- Valeur : `[Classe contrôleur FQCN, méthode]`

Exemples :

| URI | Contrôleur | Méthode |
|-----|------------|---------|
| `/` | `HomeController` | `index` |
| `/items` | `ItemController` | `index` |
| `/categories` | `CategoryController` | `index` |

## Dépendances

- RequestObject (`App\\Kernel\\RequestObject`)
- AuthBearerMiddleware (`App\\Middleware\\AuthBearerMiddleware`)
- ResponseInterface (`App\\Kernel\\Interfaces\\ResponseInterface`)

Réponses utilisées :
- ClientErrorResponse (`App\\Services\\Responses\\ClientErrorResponse`)
- ErrorResponse (`App\\Services\\Responses\\ErrorResponse`)

## Exemples

```php
// public/index.php
require_once '../vendor/Autoload.php';

use App\\Kernel\\RouterObject;

$router = new RouterObject();
$response = $router->route();
$response->send();
```

Requête « /unknown » → 404. Requête « /items » → appelle ItemController::index().

## Gestion des erreurs

- 404 Not Found : route absente
- 500 Internal Server Error : exception à l’exécution (classe introuvable, méthode absente, erreurs métier, BD, fichiers…)

## Bonnes pratiques

1. Organiser les routes logiquement
2. Nommage cohérent des contrôleurs (Suffixe Controller, PascalCase)
3. Méthodes cohérentes (index(), méthodes dédiées par verbe HTTP si besoin)
4. Gérer les exceptions côté contrôleur et retourner une réponse adaptée
5. Accepter et utiliser le middleware d’authentification injecté

## Performance

- Recherche de route en O(1) (lookup tableau)
- Instanciation du contrôleur et du middleware à chaque requête

## Tests (exemples)

```php
class RouterObjectTest extends TestCase
{
    public function testValidRoute()
    {
        $router = new RouterObject();
        $response = $router->route();
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
```

## Documentation liée

- [RequestObject](./RequestObject.md) — Extraction de l’URI et données HTTP
- [AbstractController](./AbstractController.md) — Contrats des contrôleurs
- [ResponseInterface](./AbstractResponse.md) — Contrat des réponses
