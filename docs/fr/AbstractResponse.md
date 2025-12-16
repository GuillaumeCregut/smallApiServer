# Documentation d’AbstractResponse

## Vue d’ensemble

`AbstractResponse` est une classe de base abstraite qui sert de fondation à tous les objets de réponse HTTP dans le framework Attaquant. Elle implémente `ResponseInterface` et définit la structure cœur pour gérer les codes statut HTTP, les en-têtes et le corps de la réponse. Cette classe assure une gestion cohérente des réponses dans l’application.

Emplacement : `Kernel/AbstractResponse.php`
Espace de noms : `App\Kernel`
Patrons : Classe de base abstraite, Template Method Pattern

## Définition de classe

```php
abstract class AbstractResponse implements ResponseInterface
```

## Propriétés

### Propriétés protégées

#### `$statusCode` (int)
- Type : `int`
- Description : Code statut HTTP de la réponse
- Défaut : `200` (OK)
- Portée : Protégée
- Valeurs communes :
  - `200` - OK
  - `201` - Created
  - `400` - Bad Request
  - `401` - Unauthorized
  - `404` - Not Found
  - `422` - Unprocessable Entity
  - `500` - Internal Server Error

#### `$headers` (array)
- Type : `array`
- Description : En-têtes HTTP de la réponse en paires clé/valeur
- Défaut : `[]`
- Portée : Protégée
- Format : `['Header-Name' => 'Header Value']`
- Exemples :
  - `['Content-Type' => 'application/json']`
  - `['Authorization' => 'Bearer token']`
  - `['Cache-Control' => 'no-cache']`

#### `$body` (string)
- Type : `string`
- Description : Contenu du corps de réponse (généralement JSON ou HTML)
- Défaut : `''`
- Portée : Protégée
- Contenu : Données renvoyées au client

## Méthodes

### Méthodes abstraites

#### `setBody(mixed $content): void`

```php
abstract public function setBody(mixed $content): void;
```

Description :
Méthode abstraite à implémenter par les classes concrètes. Définit la manière de définir et formater le corps de réponse.

Paramètres :
- `$content` (mixed) - Contenu du corps (array, string, objet, etc.)

Type de retour : void

Exigences d’implémentation :
- Convertir le contenu au format approprié (JSON, HTML…)
- Définir la propriété `$body`
- Éventuellement définir l’en-tête Content-Type

Exemple :
```php
public function setBody(mixed $content): void
{
    $this->body = json_encode($content);
    $this->setHeader('Content-Type', 'application/json');
}
```

---

#### `sendReponse(): void`

```php
abstract public function sendReponse(): void;
```

Description :
Méthode abstraite à implémenter par les classes concrètes. Gère la logique spécifique avant l’envoi.

Paramètres : Aucun

Type de retour : void

Exigences d’implémentation :
- Opérations pré-envoi éventuelles
- Définition d’en-têtes additionnels
- Validation de l’état de la réponse
- Appelée avant l’envoi des en-têtes et du corps

Note : le nom contient une coquille (`sendReponse` au lieu de `sendResponse`), conservée pour compatibilité.

Exemple :
```php
public function sendReponse(): void
{
    // Opérations pré-envoi
}
```

---

### Méthodes publiques

#### `setStatusCode(int $code): void`

```php
public function setStatusCode(int $code): void
```

Description :
Définit le code statut HTTP de la réponse.

Paramètres :
- `$code` (int) - Code HTTP (100–599)

Type de retour : void

Comportement :
- Met à jour `$statusCode`
- Pas de validation stricte du code
- Peut être appelé plusieurs fois (dernière valeur conservée)

Exemple :
```php
$response = new JsonResponse();
$response->setStatusCode(201);
$response->setBody(['id' => 1, 'name' => 'John']);
```

---

#### `setHeader(string $name, string $value): void`

```php
public function setHeader(string $name, string $value): void
```

Description :
Définit un en-tête HTTP de la réponse.

Paramètres :
- `$name` (string) - Nom (ex. Content-Type)
- `$value` (string) - Valeur

Type de retour : void

Comportement :
- Stocke l’en-tête dans `$headers`
- Écrase une clé existante
- Envoi sous la forme `Header-Name: Header Value`

Exemple :
```php
$response = new JsonResponse();
$response->setHeader('Content-Type', 'application/json');
$response->setHeader('Cache-Control', 'no-cache');
```

---

#### `send(): void`

```php
public function send(): void
```

Description :
Envoie la réponse HTTP complète au client. Orchestration de l’envoi du statut, des en-têtes et du corps.

Paramètres : Aucun

Type de retour : void

Comportement :

1. Appelle `sendReponse()`
2. Définit le code via `http_response_code()`
3. Envoie les en-têtes avec `header()`
4. Émet le corps en `echo`

Ordre d’exécution :
```
send() → sendReponse() → http_response_code() → header()* → echo $body
```

Notes importantes :
- À appeler une seule fois par requête
- Aucun output avant l’appel
- Les en-têtes précèdent le corps

Exemple :
```php
$response = new JsonResponse();
$response->setStatusCode(200);
$response->setHeader('Content-Type', 'application/json');
$response->setBody(['status' => 'success']);
$response->send();
```

---

## Patron d’implémentation

AbstractResponse applique le Template Method Pattern :
- `send()` définit l’algorithme global
- Les sous-classes implémentent `setBody()` et `sendReponse()`

### Créer une réponse concrète

```php
class CustomResponse extends AbstractResponse
{
    public function setBody(mixed $content): void
    {
        $this->body = (string) $content;
        $this->setHeader('Content-Type', 'text/plain');
    }

    public function sendReponse(): void
    {
        // Opérations pré-envoi
    }
}
```

## Interface Response

AbstractResponse implémente `ResponseInterface` :

```php
interface ResponseInterface
{
    public function setStatusCode(int $code): void;
    public function setHeader(string $name, string $value): void;
    public function send(): void;
    public function setBody(mixed $content): void;
    public function sendReponse(): void;
}
```

## Classes de réponse intégrées

### JsonResponse
Réponses au format JSON.

```php
$response = new JsonResponse();
$response->setStatusCode(200);
$response->setBody(['key' => 'value']);
$response->send();
```

### ErrorResponse
Réponses d’erreur 500.

```php
$response = new ErrorResponse();
$response->send();
```

### ClientErrorResponse
Réponses d’erreur client (4xx).

```php
$response = new ClientErrorResponse(404);
$response->send();
```

## Exemples d’utilisation

### Réponse basique

```php
use App\Services\Responses\JsonResponse;

$response = new JsonResponse();
$response->setStatusCode(200);
$response->setBody(['message' => 'Success']);
$response->send();
```

### Réponse avec en-têtes personnalisés

```php
$response = new JsonResponse();
$response->setStatusCode(200);
$response->setHeader('Content-Type', 'application/json');
$response->setHeader('X-API-Version', '1.0');
$response->setHeader('Cache-Control', 'no-cache');
$response->setBody(['data' => 'value']);
$response->send();
```

### Réponse d’erreur

```php
$response = new ErrorResponse(500);
$response->setHeader('Content-Type', 'application/json');
$response->send();
```

### Réponse « Created »

```php
$response = new JsonResponse();
$response->setStatusCode(201);
$response->setHeader('Location', '/api/users/123');
$response->setBody(['id' => 123, 'name' => 'John']);
$response->send();
```

### Dans un contrôleur

```php
public function index(): ResponseInterface
{
    $data = $this->model->getAll();
    
    $response = new JsonResponse();
    $response->setStatusCode(200);
    $response->setBody($data);
    
    return $response;
}
```

## Structure d’une réponse HTTP

```
HTTP/1.1 200 OK
Content-Type: application/json
Content-Length: 27
Cache-Control: no-cache

{"status":"success","id":1}
```

Composants :
1. Ligne de statut
2. En-têtes
3. Ligne vide
4. Corps

## Bonnes pratiques

### 1. Définir un code approprié

```php
$response->setStatusCode(201);  // Created
```

### 2. Définir Content-Type

```php
$response->setHeader('Content-Type', 'application/json');
```

### 3. Utiliser les classes adaptées

```php
$response = new JsonResponse();
```

### 4. Définir les en-têtes avant l’envoi

```php
$response->setHeader('X-Custom', 'value');
$response->send();
```

### 5. Retourner ResponseInterface depuis les contrôleurs

```php
public function index(): ResponseInterface
{
    return new JsonResponse();
}
```

## Modèles courants

### Succès

```php
$response = new JsonResponse();
$response->setStatusCode(200);
$response->setBody(['status' => 'success', 'data' => $data]);
$response->send();
```

### Création

```php
$response = new JsonResponse();
$response->setStatusCode(201);
$response->setHeader('Location', '/api/resource/' . $id);
$response->setBody(['id' => $id, 'data' => $data]);
$response->send();
```

### Erreur

```php
$response = new ErrorResponse(500);
$response->setHeader('Content-Type', 'application/json');
$response->send();
```

### Erreur de validation

```php
$response = new ClientErrorResponse(422);
$response->setBody(['errors' => $validationErrors]);
$response->send();
```

## Gestion des erreurs

### « Headers already sent »

Cause : un output a été émis avant `send()`.

Solution :
- Aucun echo/print/var_dump avant `send()`

### Code statut invalide

- Utiliser des codes 100–599 (même si la classe n’applique pas la validation).

## Considérations de performance

1. Chaque en-tête augmente la taille de la réponse
2. La taille du corps impacte la bande passante
3. Envisager la compression pour de gros payloads
4. Utiliser le cache avec les bons en-têtes

## Tests

### Exemple de test unitaire

```php
use PHPUnit\Framework\TestCase;
use App\Services\Responses\JsonResponse;

class AbstractResponseTest extends TestCase
{
    public function testSetStatusCode()
    {
        $response = new JsonResponse();
        $response->setStatusCode(201);
        $this->assertEquals(201, $response->statusCode);
    }

    public function testSetHeader()
    {
        $response = new JsonResponse();
        $response->setHeader('X-Custom', 'value');
        $this->assertEquals('value', $response->headers['X-Custom']);
    }

    public function testSetBody()
    {
        $response = new JsonResponse();
        $response->setBody(['key' => 'value']);
        $this->assertNotEmpty($response->body);
    }
}
```

## Classes liées

- ResponseInterface (`App\Kernel\Interfaces\ResponseInterface`)
- JsonResponse (`App\Services\Responses\JsonResponse`)
- ErrorResponse (`App\Services\Responses\ErrorResponse`)
- ClientErrorResponse (`App\Services\Responses\ClientErrorResponse`)

## Documentation liée

- [RouterObject](./RouterObject.md) - Retour des réponses depuis les contrôleurs
- [RequestObject](./RequestObject.md) - Données nécessaires au traitement

## Journal des modifications

### Version 1.0
- Implémentation initiale
- Classe de base pour les réponses
- Gestion du code statut
- Gestion des en-têtes
- Envoi de la réponse

## Améliorations futures

- [ ] Support de la compression
- [ ] Calcul automatique de Content-Length
- [ ] Helpers CORS
