# FileUpload Documentation

## Overview

`FileUpload` is a class that wraps PHP's file upload functionality and extends `SplFileInfo` to provide a robust, object-oriented interface for handling file uploads. It encapsulates file validation, security checks, and file movement operations. This class is used by `RequestObject` to represent uploaded files in a type-safe manner.

**Location:** `Kernel/Files/FileUpload.php`  
**Namespace:** `App\Kernel\Files`  
**Parent Class:** `SplFileInfo`

## Class Definition

```php
class FileUpload extends SplFileInfo
```

## Properties

### Private Properties

#### `$name` (string)
- **Type:** `string`
- **Description:** Original filename as provided by the client
- **Scope:** Private
- **Initialized from:** `$fileData['name']`
- **Example:** `"document.pdf"`, `"image.jpg"`

#### `$mimeType` (string)
- **Type:** `string`
- **Description:** MIME type of the uploaded file
- **Scope:** Private
- **Initialized from:** `$fileData['type']` or default `'application/octet-stream'`
- **Examples:**
  - `'image/jpeg'` - JPEG image
  - `'image/png'` - PNG image
  - `'application/pdf'` - PDF document
  - `'text/plain'` - Text file
  - `'application/octet-stream'` - Unknown type

#### `$tmp_name` (string)
- **Type:** `string`
- **Description:** Temporary path where PHP stored the uploaded file
- **Scope:** Private
- **Initialized from:** `$fileData['tmp_name']`
- **Example:** `"/tmp/php1a2b3c4d"`

#### `$size` (int)
- **Type:** `int`
- **Description:** File size in bytes
- **Scope:** Private
- **Initialized from:** `$fileData['size']`
- **Example:** `1024` (1 KB), `5242880` (5 MB)

#### `$error` (int)
- **Type:** `int`
- **Description:** PHP upload error code
- **Scope:** Private
- **Initialized from:** `$fileData['error']`
- **Values:**
  - `0` - `UPLOAD_ERR_OK` - No error
  - `1` - `UPLOAD_ERR_INI_SIZE` - Exceeds upload_max_filesize
  - `2` - `UPLOAD_ERR_FORM_SIZE` - Exceeds form MAX_FILE_SIZE
  - `3` - `UPLOAD_ERR_PARTIAL` - Partial upload
  - `4` - `UPLOAD_ERR_NO_FILE` - No file uploaded
  - `6` - `UPLOAD_ERR_NO_TMP_DIR` - Missing temp directory
  - `7` - `UPLOAD_ERR_CANT_WRITE` - Cannot write to disk
  - `8` - `UPLOAD_ERR_EXTENSION` - Extension stopped upload

#### `$full_path` (string)
- **Type:** `string`
- **Description:** Full path to the file (used after moving)
- **Scope:** Private
- **Initialized from:** `$fileData['full_path']` or empty string
- **Example:** `"/var/www/uploads/document.pdf"`

#### `$fileOK` (bool)
- **Type:** `bool`
- **Description:** Flag indicating if file passed validation
- **Scope:** Private
- **Default:** `false`
- **Set by:** `isValid()` method
- **Purpose:** Prevents moving invalid files

## Methods

### Constructor

```php
public function __construct(array $fileData)
```

**Description:**  
Initializes a FileUpload object with file data from PHP's `$_FILES` superglobal or from a moved file.

**Parameters:**
- `$fileData` (array) - File data array with keys:
  - `'name'` (string) - Original filename
  - `'type'` (string) - MIME type
  - `'tmp_name'` (string) - Temporary file path
  - `'size'` (int) - File size in bytes
  - `'error'` (int) - Upload error code
  - `'full_path'` (string, optional) - Full path to file

**Return Type:** void

**Behavior:**
- Extracts file data from the array
- Sets MIME type to `'application/octet-stream'` if not provided
- Calls parent `SplFileInfo` constructor with temporary file path
- Initializes `$fileOK` to `false`

**Example:**
```php
$fileData = [
    'name' => 'document.pdf',
    'type' => 'application/pdf',
    'tmp_name' => '/tmp/php1a2b3c4d',
    'size' => 102400,
    'error' => 0,
];

$file = new FileUpload($fileData);
```

---

### Public Methods

#### `isValid(int $maxSize, array $allowedMimeTypes = []): bool`

```php
public function isValid(int $maxSize, array $allowedMimeTypes = []): bool
```

**Description:**  
Validates the uploaded file against size and MIME type restrictions.

**Parameters:**
- `$maxSize` (int) - Maximum allowed file size in bytes
- `$allowedMimeTypes` (array) - Array of allowed MIME types (empty = none allowed)

**Return Type:** `bool`

**Return Values:**
- `true` if file passes all validation checks
- `false` if file fails any validation check

**Validation Checks:**

1. **Upload Error Check:**
   - Verifies `$error === UPLOAD_ERR_OK`
   - Fails if upload had an error

2. **File Exists Check:**
   - Verifies temporary file exists using `is_file()`
   - Fails if file doesn't exist

3. **Size Check:**
   - Verifies `$size <= $maxSize`
   - Fails if file exceeds maximum size

4. **MIME Type Check:**
   - Verifies MIME type is in `$allowedMimeTypes`
   - Fails if MIME type not in allowed list

5. **Upload File Check:**
   - Verifies file is a valid upload using `is_uploaded_file()`
   - Fails if not a valid upload

**Side Effects:**
- Sets `$fileOK` property to validation result
- Required before calling `move()`

**Validation Flow:**
```
isValid() called
    ↓
Check upload error
    ├─ ERROR → Return false
    └─ OK → Continue
    ↓
Check file exists
    ├─ NOT EXISTS → Return false
    └─ EXISTS → Continue
    ↓
Check file size
    ├─ TOO LARGE → Return false
    └─ OK → Continue
    ↓
Check MIME type
    ├─ NOT ALLOWED → Return false
    └─ ALLOWED → Continue
    ↓
Check is_uploaded_file()
    ├─ INVALID → Return false
    └─ VALID → Set $fileOK = true, Return true
```

**Example:**
```php
$file = new FileUpload($fileData);

// Validate with size limit and allowed types
$isValid = $file->isValid(
    5242880,  // 5 MB
    ['image/jpeg', 'image/png', 'application/pdf']
);

if ($isValid) {
    // File is valid, can be moved
} else {
    // File is invalid
}
```

---

#### `move(string $directory, ?string $name = null): FileUpload`

```php
public function move(string $directory, ?string $name = null): FileUpload
```

**Description:**  
Moves the uploaded file from the temporary directory to the specified destination directory.

**Parameters:**
- `$directory` (string) - Destination directory path
- `$name` (string|null) - New filename (optional, uses original if null)

**Return Type:** `FileUpload`

**Return Values:**
- New FileUpload object representing the moved file
- Throws exception on failure

**Exceptions:**
- `Exception` - If file is not valid (not passed `isValid()`)
- `Exception` - If directory cannot be created
- `Exception` - If directory is not writable
- `Exception` - If file cannot be moved
- `Exception` - With error message from `getErrorMessage()`

**Behavior:**

1. **Validation Check:**
   - Throws exception if `$fileOK` is false
   - File must pass `isValid()` before moving

2. **Directory Handling:**
   - Creates directory if it doesn't exist
   - Creates parent directories recursively
   - Checks if directory is writable
   - Throws exception if directory cannot be created or is not writable

3. **File Movement:**
   - Constructs target file path
   - Uses `move_uploaded_file()` for secure movement
   - Sets file permissions to 0666 (masked by umask)
   - Returns new FileUpload object for moved file

4. **Return Value:**
   - Returns new FileUpload object with updated path
   - Original FileUpload object remains unchanged

**Move Flow:**
```
move() called
    ↓
Check if file is valid ($fileOK)
    ├─ NOT VALID → Throw exception
    └─ VALID → Continue
    ↓
Check/create destination directory
    ├─ CANNOT CREATE → Throw exception
    ├─ NOT WRITABLE → Throw exception
    └─ OK → Continue
    ↓
Construct target path
    ↓
Move file using move_uploaded_file()
    ├─ FAILED → Throw exception
    └─ SUCCESS → Continue
    ↓
Set file permissions
    ↓
Return new FileUpload object
```

**Example:**
```php
$file = new FileUpload($fileData);

// Validate file
if (!$file->isValid(5242880, ['image/jpeg', 'image/png'])) {
    echo "File validation failed";
    return;
}

try {
    // Move file to uploads directory
    $movedFile = $file->move('/var/www/uploads');
    
    // Or move with custom name
    $movedFile = $file->move('/var/www/uploads', 'custom_name.jpg');
    
    echo "File moved to: " . $movedFile->getFullPath();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

---

### Getter Methods

#### `getName(): string`

```php
public function getName(): string
```

**Description:**  
Returns the original filename as provided by the client.

**Parameters:** None

**Return Type:** `string`

**Return Values:**
- Original filename

**Example:**
```php
$name = $file->getName();
// Result: "document.pdf"
```

---

#### `getMimeType(): string`

```php
public function getMimeType(): string
```

**Description:**  
Returns the MIME type of the file.

**Parameters:** None

**Return Type:** `string`

**Return Values:**
- MIME type string

**Common MIME Types:**
- `'image/jpeg'` - JPEG image
- `'image/png'` - PNG image
- `'image/gif'` - GIF image
- `'application/pdf'` - PDF document
- `'text/plain'` - Text file
- `'text/csv'` - CSV file
- `'application/json'` - JSON file
- `'application/octet-stream'` - Unknown/binary

**Example:**
```php
$mimeType = $file->getMimeType();
// Result: "application/pdf"
```

---

#### `getSize(): int`

```php
public function getSize(): int
```

**Description:**  
Returns the file size in bytes.

**Parameters:** None

**Return Type:** `int`

**Return Values:**
- File size in bytes

**Example:**
```php
$size = $file->getSize();
// Result: 102400 (100 KB)

// Convert to human-readable format
$sizeInMB = $size / (1024 * 1024);
```

---

#### `getError(): int`

```php
public function getError(): int
```

**Description:**  
Returns the PHP upload error code.

**Parameters:** None

**Return Type:** `int`

**Return Values:**
- Upload error code (0 = no error)

**Error Codes:**

| Code | Constant | Meaning |
|------|----------|---------|
| 0 | UPLOAD_ERR_OK | No error |
| 1 | UPLOAD_ERR_INI_SIZE | Exceeds upload_max_filesize |
| 2 | UPLOAD_ERR_FORM_SIZE | Exceeds form MAX_FILE_SIZE |
| 3 | UPLOAD_ERR_PARTIAL | Partial upload |
| 4 | UPLOAD_ERR_NO_FILE | No file uploaded |
| 6 | UPLOAD_ERR_NO_TMP_DIR | Missing temp directory |
| 7 | UPLOAD_ERR_CANT_WRITE | Cannot write to disk |
| 8 | UPLOAD_ERR_EXTENSION | Extension stopped upload |

**Example:**
```php
$error = $file->getError();
if ($error !== UPLOAD_ERR_OK) {
    echo "Upload error: " . $error;
}
```

---

#### `getFullPath(): string`

```php
public function getFullPath(): string
```

**Description:**  
Returns the full path to the file (after moving).

**Parameters:** None

**Return Type:** `string`

**Return Values:**
- Full file path

**Example:**
```php
$path = $file->getFullPath();
// Result: "/var/www/uploads/document.pdf"
```

---

### Private Methods

#### `getFName(string $name): string`

```php
private function getFName(string $name): string
```

**Description:**  
Extracts the filename from a path (removes directory components).

**Parameters:**
- `$name` (string) - Filename or path

**Return Type:** `string`

**Return Values:**
- Filename without directory path

**Behavior:**
- Handles both forward and backward slashes
- Returns original name if no path separators found
- Extracts filename after last separator

**Example:**
```php
$result = $this->getFName('path/to/file.txt');
// Result: "file.txt"

$result = $this->getFName('file.txt');
// Result: "file.txt"
```

---

#### `getErrorMessage(int $error): string`

```php
private function getErrorMessage(int $error): string
```

**Description:**  
Returns a human-readable error message for an upload error code.

**Parameters:**
- `$error` (int) - Upload error code

**Return Type:** `string`

**Return Values:**
- Human-readable error message

**Error Messages:**

| Code | Message |
|------|---------|
| 1 | The file exceeds your upload_max_filesize ini directive |
| 2 | The file exceeds the upload limit defined in your form. |
| 3 | The file was only partially uploaded. |
| 4 | No file was uploaded. |
| 6 | File could not be uploaded: missing temporary directory. |
| 7 | The file could not be written on disk. |
| 8 | File upload was stopped by a PHP extension. |
| Other | The file was not uploaded due to an unknown error. |

**Example:**
```php
$message = $this->getErrorMessage(1);
// Result: "The file exceeds your upload_max_filesize ini directive"
```

---

## Inherited Methods from SplFileInfo

FileUpload extends `SplFileInfo`, providing access to file information methods:

```php
$file->getBasename();      // Get filename with extension
$file->getExtension();     // Get file extension
$file->getPathname();      // Get full path
$file->getPath();          // Get directory path
$file->getFilename();      // Get filename
$file->getType();          // Get file type (file, dir, link)
$file->isFile();           // Check if is file
$file->isDir();            // Check if is directory
$file->isLink();           // Check if is symlink
$file->isReadable();       // Check if readable
$file->isWritable();       // Check if writable
$file->isExecutable();     // Check if executable
```

## Usage Examples

### Basic File Upload Handling

```php
// In controller
public function uploadFile(): ResponseInterface
{
    $files = $this->request->getFile('upload');
    
    if (!$files) {
        return $this->returnError(400);
    }

    foreach ($files as $file) {
        // Validate file
        if (!$file->isValid(5242880, ['image/jpeg', 'image/png'])) {
            return $this->returnError(422);
        }

        try {
            // Move file
            $movedFile = $file->move('/var/www/uploads');
            
            // Save file info to database
            $this->model->saveFile([
                'name' => $movedFile->getName(),
                'path' => $movedFile->getFullPath(),
                'size' => $movedFile->getSize(),
                'mime' => $movedFile->getMimeType(),
            ]);
        } catch (Exception $e) {
            return $this->returnError(500);
        }
    }

    $response = new JsonResponse();
    $response->setStatusCode(201);
    return $response;
}
```

### Image Upload with Validation

```php
public function uploadImage(): ResponseInterface
{
    $files = $this->request->getFile('image');
    
    if (!$files) {
        return $this->returnError(400);
    }

    $file = $files[0];
    
    // Validate image
    if (!$file->isValid(2097152, ['image/jpeg', 'image/png', 'image/gif'])) {
        return $this->returnError(422);
    }

    try {
        $movedFile = $file->move('/var/www/uploads/images');
        
        $response = new JsonResponse();
        $response->setStatusCode(201);
        $response->setBody([
            'url' => '/uploads/images/' . $movedFile->getName(),
            'size' => $movedFile->getSize(),
        ]);
        return $response;
    } catch (Exception $e) {
        return $this->returnError(500);
    }
}
```

### Document Upload with Custom Name

```php
public function uploadDocument(): ResponseInterface
{
    $files = $this->request->getFile('document');
    
    if (!$files) {
        return $this->returnError(400);
    }

    $file = $files[0];
    
    // Validate document
    if (!$file->isValid(10485760, ['application/pdf', 'application/msword'])) {
        return $this->returnError(422);
    }

    try {
        // Generate unique filename
        $uniqueName = uniqid() . '_' . $file->getName();
        
        $movedFile = $file->move('/var/www/uploads/documents', $uniqueName);
        
        $response = new JsonResponse();
        $response->setStatusCode(201);
        $response->setBody(['path' => $movedFile->getFullPath()]);
        return $response;
    } catch (Exception $e) {
        return $this->returnError(500);
    }
}
```

### Multiple File Upload

```php
public function uploadMultiple(): ResponseInterface
{
    $files = $this->request->getFile('files');
    
    if (!$files) {
        return $this->returnError(400);
    }

    $uploadedFiles = [];
    
    foreach ($files as $file) {
        // Validate each file
        if (!$file->isValid(5242880, ['image/jpeg', 'image/png'])) {
            continue;  // Skip invalid files
        }

        try {
            $movedFile = $file->move('/var/www/uploads');
            $uploadedFiles[] = [
                'name' => $movedFile->getName(),
                'path' => $movedFile->getFullPath(),
                'size' => $movedFile->getSize(),
            ];
        } catch (Exception $e) {
            // Log error and continue
            continue;
        }
    }

    if (empty($uploadedFiles)) {
        return $this->returnError(422);
    }

    $response = new JsonResponse();
    $response->setStatusCode(201);
    $response->setBody(['files' => $uploadedFiles]);
    return $response;
}
```

### Error Handling

```php
public function uploadWithErrorHandling(): ResponseInterface
{
    $files = $this->request->getFile('upload');
    
    if (!$files) {
        return $this->returnError(400);
    }

    $file = $files[0];
    
    // Check for upload errors
    if ($file->getError() !== UPLOAD_ERR_OK) {
        $response = new JsonResponse();
        $response->setStatusCode(422);
        $response->setBody([
            'error' => 'Upload failed',
            'code' => $file->getError(),
        ]);
        return $response;
    }

    // Validate file
    if (!$file->isValid(5242880, ['image/jpeg', 'image/png'])) {
        $response = new JsonResponse();
        $response->setStatusCode(422);
        $response->setBody([
            'error' => 'File validation failed',
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
        ]);
        return $response;
    }

    try {
        $movedFile = $file->move('/var/www/uploads');
        
        $response = new JsonResponse();
        $response->setStatusCode(201);
        $response->setBody(['path' => $movedFile->getFullPath()]);
        return $response;
    } catch (Exception $e) {
        $response = new JsonResponse();
        $response->setStatusCode(500);
        $response->setBody(['error' => $e->getMessage()]);
        return $response;
    }
}
```

## Best Practices

### 1. Always Validate Before Moving

```php
// Good
if ($file->isValid($maxSize, $allowedTypes)) {
    $movedFile = $file->move($directory);
}

// Avoid
$movedFile = $file->move($directory);  // No validation
```

### 2. Use Allowed MIME Types

```php
// Good
$file->isValid(5242880, ['image/jpeg', 'image/png']);

// Avoid
$file->isValid(5242880, []);  // Allows none types
```

### 3. Handle Exceptions

```php
// Good
try {
    $movedFile = $file->move($directory);
} catch (Exception $e) {
    // Handle error
}

// Avoid
$movedFile = $file->move($directory);  // No error handling
```

### 4. Use Unique Filenames

```php
// Good
$uniqueName = uniqid() . '_' . $file->getName();
$movedFile = $file->move($directory, $uniqueName);

// Avoid
$movedFile = $file->move($directory);  // May overwrite existing files
```

### 5. Check File Size

```php
// Good
$maxSize = 5242880;  // 5 MB
if ($file->isValid($maxSize, $allowedTypes)) {
    // Process file
}

// Avoid
if ($file->isValid(PHP_INT_MAX, $allowedTypes)) {  // No limit
```

### 6. Validate MIME Types

```php
// Good
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
$file->isValid($maxSize, $allowedTypes);

// Avoid
$file->isValid($maxSize, []);  // No MIME type validation
```

## Security Considerations

### 1. File Type Validation
- Always validate MIME types
- Don't rely solely on file extension
- Use whitelist approach (allow specific types)

### 2. File Size Limits
- Set reasonable maximum file sizes
- Prevent disk space exhaustion
- Consider server resources

### 3. Directory Permissions
- Ensure upload directory is writable
- Restrict access to uploaded files
- Don't store uploads in web root if possible

### 4. Filename Sanitization
- Use unique filenames to prevent overwrites
- Avoid user-provided filenames directly
- Remove path traversal characters

### 5. Virus Scanning
- Consider integrating antivirus scanning
- Scan files before storing
- Quarantine suspicious files

## Error Handling

### Upload Errors

| Error | Cause | Solution |
|-------|-------|----------|
| UPLOAD_ERR_INI_SIZE | File exceeds upload_max_filesize | Increase php.ini setting or reduce file size |
| UPLOAD_ERR_FORM_SIZE | File exceeds form MAX_FILE_SIZE | Increase form limit or reduce file size |
| UPLOAD_ERR_PARTIAL | Partial upload | Retry upload |
| UPLOAD_ERR_NO_FILE | No file uploaded | Ensure file is selected |
| UPLOAD_ERR_NO_TMP_DIR | Missing temp directory | Check server configuration |
| UPLOAD_ERR_CANT_WRITE | Cannot write to disk | Check disk space and permissions |
| UPLOAD_ERR_EXTENSION | Extension stopped upload | Check PHP extensions |

### Move Errors

| Error | Cause | Solution |
|-------|-------|----------|
| File not valid | Validation failed | Call isValid() before move() |
| Directory can't be created | Permission denied | Check directory permissions |
| Directory not writable | Permission denied | Change directory permissions |
| Could not move file | Move operation failed | Check disk space and permissions |

## Testing

### Unit Test Example

```php
use PHPUnit\Framework\TestCase;
use App\Kernel\Files\FileUpload;

class FileUploadTest extends TestCase
{
    public function testConstructor()
    {
        $fileData = [
            'name' => 'test.pdf',
            'type' => 'application/pdf',
            'tmp_name' => '/tmp/test',
            'size' => 1024,
            'error' => 0,
        ];

        $file = new FileUpload($fileData);
        $this->assertEquals('test.pdf', $file->getName());
        $this->assertEquals('application/pdf', $file->getMimeType());
    }

    public function testIsValid()
    {
        $fileData = [
            'name' => 'test.pdf',
            'type' => 'application/pdf',
            'tmp_name' => '/tmp/test',
            'size' => 1024,
            'error' => 0,
        ];

        $file = new FileUpload($fileData);
        $isValid = $file->isValid(5242880, ['application/pdf']);
        $this->assertIsBool($isValid);
    }
}
```

## Related Classes

- **RequestObject** (`App\Kernel\RequestObject`)
  - Creates FileUpload objects from `$_FILES`

- **FileFormator** (`App\Kernel\Files\FileFormator`)
  - Normalizes `$_FILES` structure

## Related Documentation

- [RequestObject Documentation](./RequestObject.md) - How files are accessed from requests
- [AbstractController Documentation](./AbstractController.md) - How to handle file uploads in controllers

## Changelog

### Version 1.0
- Initial implementation
- File validation
- File movement
- Error handling
- SplFileInfo integration

## Future Enhancements

- [ ] Compression support

