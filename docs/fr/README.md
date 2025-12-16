# Résumé de la documentation

Bienvenue dans la documentation du framework SmallMVC. Ce répertoire contient des guides complets pour comprendre et utiliser les composants cœur du framework.

## Navigation rapide

### Composants du noyau

#### [Documentation de RouterObject](./RouterObject.md)
Dispatcheur central qui gère le routage des requêtes HTTP et l’appel des contrôleurs.

**Sujets clés :**
- Configuration et gestion des routes
- Dispatch des requêtes vers les contrôleurs
- Gestion des erreurs (404, 500)
- Intégration middleware
- Bonnes pratiques de routage

**Quand l’utiliser :** Comprendre comment les requêtes sont routées vers les contrôleurs, ajouter de nouvelles routes, déboguer les problèmes de routage.

---

#### [Documentation de RequestObject](./RequestObject.md)
Classe singleton qui encapsule toutes les données de requête HTTP et fournit un accès unifié aux informations de la requête.

**Sujets clés :**
- Gestion des méthodes HTTP (GET, POST, PUT, PATCH, DELETE)
- Extraction de données depuis plusieurs sources
- Gestion des uploads de fichiers
- Gestion de session
- Analyse de l’en-tête Authorization

**Quand l’utiliser :** Accéder aux données de requête dans les contrôleurs, gérer les uploads, gérer les sessions, extraire les jetons d’authentification.

---

#### [Documentation d’AbstractController](./AbstractController.md)
Classe de base abstraite fournissant les fondations de tous les contrôleurs avec gestion intégrée des requêtes et de l’authentification.

**Sujets clés :**
- Initialisation et configuration du contrôleur
- Vérification d’authentification
- Gestion des réponses d’erreur
- Accès aux données de requête
- Schémas CRUD RESTful

**Quand l’utiliser :** Créer de nouveaux contrôleurs, implémenter des vérifications d’authentification, gérer les méthodes HTTP, retourner des réponses.

---

#### [Documentation d’AbstractResponse](./AbstractResponse.md)
Classe de base abstraite pour les objets réponse HTTP, avec gestion du code statut, des en-têtes et du corps de réponse.

**Sujets clés :**
- Gestion des codes statut
- Gestion des en-têtes HTTP
- Formatage du corps de réponse
- Envoi de la réponse
- Classes de réponse intégrées

**Quand l’utiliser :** Comprendre la gestion des réponses, créer des types de réponses personnalisés, gérer les en-têtes et codes HTTP.

---

#### [Documentation de FileUpload](./FileUpload.md)
Classe pour gérer les uploads de fichiers avec validation, contrôles de sécurité et déplacement sécurisé des fichiers.

**Sujets clés :**
- Validation des fichiers (taille, type MIME)
- Déplacement sécurisé des fichiers
- Gestion des erreurs
- Accès aux informations du fichier
- Bonnes pratiques de sécurité

**Quand l’utiliser :** Traiter des uploads, valider types et tailles, déplacer les fichiers vers des répertoires de stockage.

---

## Vue d’ensemble de l’architecture

```
Requête HTTP
    ↓
public/index.php (Point d’entrée)
    ↓
RouterObject (Dispatcheur de routes)
    ├─ Utilise RequestObject (Récupérer les données)
    ├─ Instancie AuthBearerMiddleware
    └─ Appelle le contrôleur approprié
    ↓
Contrôleur (Logique métier)
    ├─ Utilise RequestObject (Accès aux données)
    ├─ Utilise des Models (Base de données)
    └─ Retourne ResponseInterface
    ↓
Réponse (JSON/Erreur)
    ↓
Réponse HTTP
```

## Structure de la documentation

### Par type de composant

**Composants Kernel :**
- [RouterObject](./RouterObject.md) - Routage des requêtes
- [RequestObject](./RequestObject.md) - Gestion des données HTTP
- [AbstractController](./AbstractController.md) - Classe de base contrôleur
- [AbstractResponse](./AbstractResponse.md) - Classe de base réponse
- [FileUpload](./FileUpload.md) - Gestion des uploads

**Controllers :**
- Voir le répertoire `Controllers/` du projet

**Models :**
- Voir le répertoire `Models/`

**Services :**
- Voir le répertoire `Services/`

**Middleware :**
- Voir le répertoire `middleware/`

### Par cas d’usage

**Je veux…**

- **Ajouter un nouvel endpoint API**
  1. Lire [RouterObject](./RouterObject.md) - section Configuration
  2. Créer un nouveau Controller
  3. Ajouter la route dans RouterObject

- **Accéder aux données dans mon contrôleur**
  1. Lire [RequestObject](./RequestObject.md) - section Exemples d’usage
  2. Utiliser `RequestObject::getRequestInstance()`
  3. Appeler les getters appropriés

- **Gérer des uploads**
  1. Lire [RequestObject](./RequestObject.md) - section Fichiers
  2. Utiliser `$request->getFile()` ou `$request->getFiles()`

- **Implémenter l’authentification**
  1. Lire [RequestObject](./RequestObject.md) - section Authentification
  2. Utiliser `$request->getAuthUser()` pour extraire le Bearer token
  3. Valider le jeton dans le middleware

- **Déboguer le routage**
  1. Lire [RouterObject](./RouterObject.md) - section Gestion des erreurs
  2. Vérifier la configuration des routes
  3. Vérifier l’existence du contrôleur et de la méthode

- **Gérer la session**
  1. Lire [RequestObject](./RequestObject.md) - section Session
  2. Utiliser `$request->getSessionValue()` et `$request->setSessionValue()`

- **Créer un nouveau contrôleur**
  1. Lire [AbstractController](./AbstractController.md) - section Créer un contrôleur concret
  2. Étendre AbstractController
  3. Implémenter `index()`
  4. Ajouter la route dans RouterObject

- **Gérer des réponses HTTP**
  1. Lire [AbstractResponse](./AbstractResponse.md) - section Exemples
  2. Créer un objet réponse (JsonResponse, ErrorResponse, etc.)
  3. Définir code statut et en-têtes
  4. Définir le corps
  5. Retourner depuis le contrôleur

- **Traiter des uploads**
  1. Lire [FileUpload](./FileUpload.md) - section Exemples
  2. `$request->getFile('field_name')`
  3. Valider : `$file->isValid($maxSize, $allowedTypes)`
  4. Déplacer : `$file->move($directory, $name)`
  5. Gérer les exceptions

## Concepts clés

### Patron Singleton
RouterObject et RequestObject utilisent le pattern Singleton pour garantir une seule instance pendant le cycle de vie de l’application.

**Avantages :**
- Source de vérité unique
- Efficacité mémoire
- Accès cohérent aux données

### Fusion des données de requête
RequestObject fusionne intelligemment les données issues de :
- Paramètres de requête (`$_GET`)
- Données de formulaire (`$_POST`)
- Corps JSON
- Paramètres d’URL (extrait `id`)

**Priorité de fusion (POST) :**
1. `$_POST` (priorité la plus haute)
2. Corps JSON
3. `$_GET` (priorité la plus basse)

### Intégration middleware
RouterObject instancie et injecte automatiquement `AuthBearerMiddleware` dans les contrôleurs.

### Gestion des erreurs
Le framework utilise des codes HTTP standardisés :
- **404** - Route introuvable
- **500** - Erreur interne
- **401** - Non autorisé
- **422** - Entité non traitable

## Tâches courantes

### Ajouter une nouvelle route

1. Ouvrir `Kernel/RouterObject.php`
2. Ajouter une entrée au tableau `$routes` :
   ```php
   'users' => ['\App\Controllers\UserController', 'index'],
   ```
3. Créer le contrôleur correspondant dans `Controllers/`
4. Implémenter `index()` retournant `ResponseInterface`

Voir [RouterObject](./RouterObject.md#adding-new-routes) pour les détails.

### Accéder aux données de requête

```php
$request = RequestObject::getRequestInstance();

// Méthode HTTP
$method = $request->getMethod();

// Toutes les données
$data = $request->getAllDatas();

// Valeur spécifique
$id = $data['id'] ?? null;
```

Voir [RequestObject](./RequestObject.md#usage-examples) pour plus d’exemples.

### Gérer des uploads de fichiers

```php
$request = RequestObject::getRequestInstance();

$files = $request->getFile('upload');
if ($files) {
    foreach ($files as $file) {
        // $file est un FileUpload
    }
}
```

Voir [RequestObject](./RequestObject.md#working-with-files).

### Extraire un jeton d’authentification

```php
$request = RequestObject::getRequestInstance();

$auth = $request->getAuthUser();
if ($auth && $auth[0] === 'Bearer') {
    $token = $auth[1];
    // Valider le jeton
}
```

Voir [RequestObject](./RequestObject.md#authentication).

## Bonnes pratiques

### 1. Utiliser le Singleton
```php
// Correct
$request = RequestObject::getRequestInstance();

// À éviter
$request = new RequestObject();
```

### 2. Valider les données
```php
$data = $request->getAllDatas();
$id = $data["id"] ?? null;

if ($id === null || !is_numeric($id)) {
    return new ClientErrorResponse(400);
}
```

### 3. Gérer les erreurs proprement
```php
try {
    // Logique métier
} catch (\Exception $e) {
    return new ErrorResponse(500);
}
```

### 4. Utiliser les types
```php
public function handleRequest(RequestObject $request): ResponseInterface
{
    // Implémentation
}
```

### 5. Organiser les routes
```php
private array $routes = [
    '' => ['\\App\\Controllers\\HomeController', 'index'],
    'api/users' => ['\\App\\Controllers\\UserController', 'index'],
    'api/products' => ['\\App\\Controllers\\ProductController', 'index'],
];
```

## Fichiers connexes

- **README principal :** voir `../README.md`
- **Structure du projet :** voir `../`
- **Code source :** voir `../Kernel/`

## Dépannage

### 404 sur une route valide
- Vérifier l’enregistrement dans RouterObject
- Vérifier le nom et l’espace de noms du contrôleur
- Vérifier l’existence du fichier

### Données introuvables
- Vérifier la méthode HTTP
- Vérifier l’envoi des données
- Utiliser `$request->getAllDatas()`

### Upload non fonctionnel
- Vérifier `maxsize` dans `.env`
- Vérifier `enctype="multipart/form-data"` dans le formulaire
- Utiliser `$request->getFile('field_name')`

### Problèmes d’authentification
- Vérifier l’en-tête Authorization
- Format: `Authorization: Bearer <token>`
- Utiliser `$request->getAuthUser()`

## Contribuer à la documentation

1. Suivre le format existant
2. Inclure des exemples de code
3. Ajouter des liens vers la doc liée
4. Mettre à jour ce README
5. Garder la doc synchronisée avec le code

## Informations de version

- **Version du framework :** 1.0
- **Version de la documentation :** 1.0
- **Dernière mise à jour :** 2024

## Ressources supplémentaires

- [README principal](../../README.md) - Vue d’ensemble et installation
- [Structure du projet](../../) - Arborescence complète
- [Code source](../../Kernel/) - Détails d’implémentation

---

**Besoin d’aide ?** Consultez la documentation spécifique à chaque composant ou la section dépannage ci-dessus.