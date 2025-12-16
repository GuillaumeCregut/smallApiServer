# Documentation de FileUpload

## Vue d’ensemble

`FileUpload` est une classe qui encapsule la fonctionnalité d’upload de fichiers de PHP et étend `SplFileInfo` pour fournir une interface robuste et orientée objet pour gérer les téléversements. Elle prend en charge la validation, les contrôles de sécurité et le déplacement des fichiers. Cette classe est utilisée par `RequestObject` pour représenter les fichiers uploadés de façon typée.

Emplacement : `Kernel/Files/FileUpload.php`
Espace de noms : `App\Kernel\Files`
Classe parente : `SplFileInfo`

## Définition de classe

```php
class FileUpload extends SplFileInfo
```

## Propriétés

### Propriétés privées

#### `$name` (string)
- Type : `string`
- Description : Nom d’origine fourni par le client
- Portée : Privée
- Initialisé depuis : `$fileData['name']`
- Exemple : `"document.pdf"`, `"image.jpg"`

#### `$mimeType` (string)
- Type : `string`
- Description : Type MIME du fichier uploadé
- Portée : Privée
- Initialisé depuis : `$fileData['type']` ou `'application/octet-stream'` par défaut
- Exemples : `image/jpeg`, `image/png`, `application/pdf`, `text/plain`, `application/octet-stream`

#### `$tmp_name` (string)
- Type : `string`
- Description : Chemin temporaire où PHP a stocké le fichier
- Portée : Privée
- Initialisé depuis : `$fileData['tmp_name']`
- Exemple : `"/tmp/php1a2b3c4d"`

#### `$size` (int)
- Type : `int`
- Description : Taille en octets
- Portée : Privée
- Initialisé depuis : `$fileData['size']`
- Exemple : `1024` (1 Ko), `5242880` (5 Mo)

#### `$error` (int)
- Type : `int`
- Description : Code d’erreur d’upload PHP
- Portée : Privée
- Initialisé depuis : `$fileData['error']`
- Valeurs : 0 (OK), 1/2/3/4/6/7/8 (codes UPLOAD_ERR_*)

#### `$full_path` (string)
- Type : `string`
- Description : Chemin complet du fichier (après déplacement)
- Portée : Privée
- Initialisé depuis : `$fileData['full_path']` ou chaîne vide

#### `$fileOK` (bool)
- Type : `bool`
- Description : Indique si le fichier a passé la validation
- Portée : Privée
- Défaut : `false`
- Défini par : `isValid()`

## Méthodes

### Constructeur

```php
public function __construct(array $fileData)
```

Description :
Initialise un objet FileUpload avec les données provenant de `$_FILES` ou d’un fichier déjà déplacé.

Paramètres :
- `$fileData` (array) avec clés `name`, `type`, `tmp_name`, `size`, `error`, `full_path` (optionnel)

Comportement :
- Extrait les données
- Définit un type par défaut si absent
- Appelle le constructeur `SplFileInfo` avec le chemin temporaire
- Initialise `$fileOK` à `false`

---

### Méthodes publiques

#### `isValid(int $maxSize, array $allowedMimeTypes = []): bool`

Valide le fichier selon la taille et les types MIME autorisés.

Vérifications :
1. Erreur d’upload (UPLOAD_ERR_OK)
2. Existence du fichier temporaire (`is_file`)
3. Taille `<= $maxSize`
4. Type MIME dans `$allowedMimeTypes`
5. Upload valide (`is_uploaded_file`)

Effets :
- Définit `$fileOK` selon le résultat

Exemple :
```php
$isValid = $file->isValid(5242880, ['image/jpeg', 'image/png', 'application/pdf']);
```

---

#### `move(string $directory, ?string $name = null): FileUpload`

Déplace le fichier téléversé du répertoire temporaire vers `$directory`.

Paramètres :
- `$directory` : dossier cible
- `$name` : nouveau nom (optionnel)

Retour :
- Nouvel objet FileUpload représentant le fichier déplacé

Exceptions :
- Fichier non valide (isValid non appelé ou échec)
- Dossier impossible à créer / non inscriptible
- Échec de `move_uploaded_file`

Comportement :
- Vérifie `$fileOK`
- Crée le dossier si nécessaire (récursif)
- Construit le chemin cible
- Déplace avec `move_uploaded_file`
- Ajuste les permissions
- Retourne un nouvel objet FileUpload

Exemple :
```php
$moved = $file->move('/var/www/uploads');
$moved = $file->move('/var/www/uploads', 'custom_name.jpg');
```

---

### Accesseurs

- `getName(): string` — Nom d’origine
- `getMimeType(): string` — Type MIME
- `getSize(): int` — Taille en octets
- `getError(): int` — Code d’erreur PHP
- `getFullPath(): string` — Chemin complet (après déplacement)

---

### Méthodes privées

- `getFName(string $name): string` — Extrait le nom de fichier d’un chemin
- `getErrorMessage(int $error): string` — Message lisible pour un code UPLOAD_ERR

---

## Méthodes héritées de SplFileInfo (extraits)

```php
$file->getBasename();
$file->getExtension();
$file->getPathname();
$file->isFile();
$file->isReadable();
$file->isWritable();
```

## Exemples d’utilisation

### Upload basique

```php
public function uploadFile(): ResponseInterface
{
    $files = $this->request->getFile('upload');
    if (!$files) { return $this->returnError(400); }

    foreach ($files as $file) {
        if (!$file->isValid(5242880, ['image/jpeg', 'image/png'])) {
            return $this->returnError(422);
        }

        try {
            $movedFile = $file->move('/var/www/uploads');
            // Persister les infos si besoin
        } catch (Exception $e) {
            return $this->returnError(500);
        }
    }

    $response = new JsonResponse();
    $response->setStatusCode(201);
    return $response;
}
```

### Upload d’image avec validation

```php
public function uploadImage(): ResponseInterface
{
    $files = $this->request->getFile('image');
    if (!$files) { return $this->returnError(400); }

    $file = $files[0];
    if (!$file->isValid(2097152, ['image/jpeg', 'image/png', 'image/gif'])) {
        return $this->returnError(422);
    }

    try {
        $moved = $file->move('/var/www/uploads/images');
        $response = new JsonResponse();
        $response->setStatusCode(201);
        $response->setBody([
            'url' => '/uploads/images/' . $moved->getName(),
            'size' => $moved->getSize(),
        ]);
        return $response;
    } catch (Exception $e) {
        return $this->returnError(500);
    }
}
```

### Upload de document avec nom personnalisé

```php
$unique = uniqid() . '_' . $file->getName();
$moved = $file->move('/var/www/uploads/documents', $unique);
```

### Upload multiple (ignorer les invalides)

```php
$uploaded = [];
foreach ($files as $file) {
    if (!$file->isValid(5242880, ['image/jpeg', 'image/png'])) { continue; }
    try {
        $moved = $file->move('/var/www/uploads');
        $uploaded[] = [
            'name' => $moved->getName(),
            'path' => $moved->getFullPath(),
            'size' => $moved->getSize(),
        ];
    } catch (Exception $e) { continue; }
}

if (empty($uploaded)) { return $this->returnError(422); }
```

## Bonnes pratiques

1. Toujours valider avant de déplacer
2. Utiliser une liste blanche de types MIME
3. Gérer les exceptions lors du déplacement
4. Utiliser des noms uniques pour éviter les collisions
5. Vérifier des limites de taille raisonnables

## Sécurité

- Valider les types, ne pas se fier aux extensions
- Définir des tailles maximales
- Vérifier les permissions du dossier d’upload
- Nettoyer/sanitariser les noms si utilisés
- Facultatif : intégration antivirus

## Gestion des erreurs

- Erreurs d’upload (codes UPLOAD_ERR_*) → adapter les messages
- Échecs de déplacement → permissions, espace disque, etc.

## Tests (exemple)

```php
use PHPUnit\Framework\TestCase;
use App\Kernel\Files\FileUpload;

class FileUploadTest extends TestCase
{
    public function testConstructor()
    {
        $fileData = [ 'name' => 'test.pdf', 'type' => 'application/pdf', 'tmp_name' => '/tmp/test', 'size' => 1024, 'error' => 0 ];
        $file = new FileUpload($fileData);
        $this->assertEquals('test.pdf', $file->getName());
        $this->assertEquals('application/pdf', $file->getMimeType());
    }

    public function testIsValid()
    {
        $fileData = [ 'name' => 'test.pdf', 'type' => 'application/pdf', 'tmp_name' => '/tmp/test', 'size' => 1024, 'error' => 0 ];
        $file = new FileUpload($fileData);
        $isValid = $file->isValid(5242880, ['application/pdf']);
        $this->assertIsBool($isValid);
    }
}
```

## Classes liées

- RequestObject (`App\Kernel\RequestObject`) — crée des FileUpload depuis `$_FILES`
- FileFormator (`App\Kernel\Files\FileFormator`) — normalise la structure de `$_FILES`

## Documentation liée

- [RequestObject](./RequestObject.md) — Accéder aux fichiers à partir des requêtes
- [AbstractController](./AbstractController.md) — Gestion des uploads côté contrôleur

## Journal

### Version 1.0
- Implémentation initiale : validation, déplacement, gestion d’erreurs, intégration SplFileInfo

## Améliorations futures

- [ ] Support de la compression
