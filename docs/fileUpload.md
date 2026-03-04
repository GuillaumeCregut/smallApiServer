# FileUpload Documentation

## Overview 

`FileUpload` is a secure file upload handler class that extends PHP's `SplFileInfo` class. It provides comprehensive validation, MIME type detection, security checks, and file movement functionality for handling user-uploaded files.

This class implements defense-in-depth security measures to protect against common file upload vulnerabilities including MIME type spoofing, double extension attacks, and malicious file uploads.

### Location
`App\Kernel\Files\FileUpload`

---

## Key Features

- **Secure MIME Type Detection**: Verifies real file type, not just client-supplied type
- **Double Extension Attack Prevention**: Blocks files with dangerous double extensions
- **Image Validation**: Validates image files using `getimagesize()`
- **File Size Validation**: Enforces maximum file size limits
- **Extension Whitelist**: Optional allowed file extensions
- **MIME Type Whitelist**: Optional allowed MIME types
- **Safe Directory Creation**: Creates upload directories with proper error handling
- **Permission Management**: Sets appropriate file permissions on moved files
- **Error Code Mapping**: Converts PHP upload errors to human-readable messages

---

## Constructor

### Signature

```php
public function __construct(array $fileData, ?FileSystemInterface $fileSystem = null)
```

### Parameters

- **`$fileData`** (array): File data array from `$_FILES` superglobal or similar structure
  
  | Key | Type | Description |
  |-----|------|-------------|
  | `name` | string | Original filename |
  | `type` | string | MIME type (from client, may be spoofed) |
  | `tmp_name` | string | Temporary file path on server |
  | `size` | int | File size in bytes |
  | `error` | int | PHP upload error code |
  | `full_path` | string (optional) | Full path if needed |

- **`$fileSystem`** (FileSystemInterface, optional): File system interface for testing. Defaults to `FileSystem` if not provided.

### Example

```php
$fileData = $_FILES['document'];
// or
$fileData = [
    'name' => 'document.pdf',
    'type' => 'application/pdf',
    'tmp_name' => '/tmp/php_uploads/abc123',
    'size' => 2048576,
    'error' => UPLOAD_ERR_OK,
];

$fileUpload = new FileUpload($fileData);
```

---

## Methods

### Public Methods

#### `isValid(int $maxSize, array $allowedMimeTypes = [], array $allowedExtensions = []): bool`

Validates the uploaded file against multiple security criteria.

**Parameters:**
- `$maxSize` (int): Maximum allowed file size in bytes
- `$allowedMimeTypes` (array): List of allowed MIME types (e.g., `['image/jpeg', 'image/png']`)
- `$allowedExtensions` (array): List of allowed file extensions (e.g., `['jpg', 'png', 'pdf']`)

**Returns:** `true` if file passes all validations, `false` otherwise

**Validation Steps:**

1. Checks for upload errors (`UPLOAD_ERR_OK`)
2. Verifies file exists at temporary location
3. Validates file size against `$maxSize`
4. Detects and blocks double extensions
5. Detects real MIME type (not client-supplied)
6. Validates against allowed MIME types
7. Validates against allowed extensions
8. For images: validates using `getimagesize()`
9. Verifies file is a legitimate uploaded file

**Example:**

```php
$fileUpload = new FileUpload($_FILES['profile_pic']);

$isValid = $fileUpload->isValid(
    maxSize: 5 * 1024 * 1024,  // 5MB
    allowedMimeTypes: ['image/jpeg', 'image/png', 'image/gif'],
    allowedExtensions: ['jpg', 'jpeg', 'png', 'gif']
);

if ($isValid) {
    // Safe to move the file
} else {
    // Handle validation failure
}
```

#### `move(string $directory, ?string $name = null): FileUpload`

Moves the validated uploaded file to the target directory.

**Parameters:**
- `$directory` (string): Destination directory path
- `$name` (string, optional): New filename. If null, uses original filename.

**Returns:** New `FileUpload` instance representing the moved file

**Throws:** `Exception` if:
- File was not validated (`isValid()` not called or returned false)
- Directory cannot be created
- Directory is not writable
- File move operation fails

**Behavior:**

1. Checks that `isValid()` was called and returned `true`
2. Creates directory if it doesn't exist
3. Verifies directory is writable
4. Moves file to target location
5. Sets file permissions to `0666` (respecting umask)
6. Returns new `FileUpload` instance with updated path

**Example:**

```php
$fileUpload = new FileUpload($_FILES['document']);

if ($fileUpload->isValid(10 * 1024 * 1024, ['application/pdf'])) {
    try {
        $moved = $fileUpload->move('/var/uploads/documents', 'user_document_2026.pdf');
        
        echo "File moved successfully to: " . $moved->getPathname();
        
    } catch (Exception $e) {
        echo "Error moving file: " . $e->getMessage();
    }
}
```

#### `getName(): string`

Returns the original filename from the upload.

```php
$name = $fileUpload->getName();  // "document.pdf"
```

#### `getMimeType(): string`

Returns the detected MIME type (not the client-supplied type).

**Note:** This value is updated during `isValid()` with the real MIME type.

```php
$mimeType = $fileUpload->getMimeType();  // "application/pdf"
```

#### `getSize(): int`

Returns the file size in bytes.

```php
$size = $fileUpload->getSize();  // 2048576
```

#### `getError(): int`

Returns the PHP upload error code.

```php
$error = $fileUpload->getError();  // UPLOAD_ERR_OK (0)
```

#### `getFullPath(): string`

Returns the full path of the file.

```php
$path = $fileUpload->getFullPath();
```

---

## Security Features

### 1. MIME Type Detection

The class detects the **real** MIME type of the file, not the client-supplied type:

```php
// Client might claim it's an image
$fileData = [
    'name' => 'innocent.jpg',
    'type' => 'image/jpeg',  // Client says it's JPEG
    'tmp_name' => '/tmp/upload123',
    'size' => 1024,
    'error' => UPLOAD_ERR_OK,
];

// But the file is actually PHP code!
// Content: <?php system($_GET['cmd']); ?>

$fileUpload = new FileUpload($fileData);

// isValid() will detect the real MIME type as 'text/x-php'
// Not 'image/jpeg', so validation fails
$isValid = $fileUpload->isValid(
    maxSize: 1024 * 1024,
    allowedMimeTypes: ['image/jpeg', 'image/png']  // Only allow images
);

// $isValid = false (prevented!)
```

**Detection Methods (in order):**

1. `finfo_open()` + `finfo_file()` (most reliable)
2. `mime_content_type()` (fallback)
3. Client-supplied type (last resort)

### 2. Double Extension Prevention

Blocks files with dangerous double extensions:

```php
// These files will be rejected:
// - file.php.txt
// - document.phar.pdf
// - image.phtml.jpg

// Dangerous extensions blocked: php, phtml, php3, php4, php5, phar, exe, sh, bat
```

**Code:**

```php
$fileUpload = new FileUpload([
    'name' => 'malicious.php.txt',
    'type' => 'text/plain',
    'tmp_name' => '/tmp/upload123',
    'size' => 1024,
    'error' => UPLOAD_ERR_OK,
]);

$isValid = $fileUpload->isValid(10000, ['text/plain']);
// $isValid = false (double extension detected)
```

### 3. Image Validation

For image files, the class validates using PHP's `getimagesize()`:

```php
// Valid image files are verified to be actual image files
// This prevents files that claim to be images but aren't

$fileUpload = new FileUpload([
    'name' => 'photo.png',
    'type' => 'image/png',
    'tmp_name' => '/tmp/fakeimage',
    'size' => 1024,
    'error' => UPLOAD_ERR_OK,
]);

// If file is not a valid PNG image, validation fails
$isValid = $fileUpload->isValid(5000000, ['image/png']);
```

**Supported Image Format Validation:**
- `image/jpeg`
- `image/png`
- `image/gif`
- `image/webp`

### 4. File Size Validation

Prevents uploading files larger than allowed:

```php
$fileUpload = new FileUpload($_FILES['large_file']);

// File is 10MB, but we only allow 5MB
$isValid = $fileUpload->isValid(5 * 1024 * 1024);  // false if file > 5MB
```

### 5. Extension Whitelist

Optional restriction to specific file extensions:

```php
$fileUpload = new FileUpload($_FILES['profile_pic']);

$isValid = $fileUpload->isValid(
    maxSize: 5 * 1024 * 1024,
    allowedExtensions: ['jpg', 'jpeg', 'png']  // Only these extensions
);

// File with .gif extension will fail (even if MIME type is allowed)
```

---

## Error Handling

### Upload Error Codes

The class provides human-readable messages for PHP upload errors:

| Error Code | Constant | Message |
|---|---|---|
| 1 | `UPLOAD_ERR_INI_SIZE` | The file exceeds your upload_max_filesize ini directive |
| 2 | `UPLOAD_ERR_FORM_SIZE` | The file exceeds the upload limit defined in your form |
| 3 | `UPLOAD_ERR_PARTIAL` | The file was only partially uploaded |
| 4 | `UPLOAD_ERR_NO_FILE` | No file was uploaded |
| 6 | `UPLOAD_ERR_NO_TMP_DIR` | File could not be uploaded: missing temporary directory |
| 7 | `UPLOAD_ERR_CANT_WRITE` | The file could not be written on disk |
| 8 | `UPLOAD_ERR_EXTENSION` | File upload was stopped by a PHP extension |

### Exception Handling

The `move()` method throws exceptions for various failure scenarios:

```php
$fileUpload = new FileUpload($_FILES['document']);

try {
    $fileUpload->move('/uploads/documents');
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    
    // Possible messages:
    // - "The file is not valid" (isValid() not called or returned false)
    // - "Directory {path} can't be created" (mkdir failed)
    // - "{path} is not writable" (directory not writable)
    // - "Could not move the file" (move operation failed)
    // - Or an upload error message from getErrorMessage()
}
```

---

## Usage Examples

### Basic File Upload

```php
<?php

namespace App\Controllers;

use App\Kernel\Files\FileUpload;

class UploadController
{
    public function uploadImage()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (!isset($_FILES['image'])) {
            echo "No file uploaded";
            return;
        }

        try {
            $fileUpload = new FileUpload($_FILES['image']);

            // Validate
            $isValid = $fileUpload->isValid(
                maxSize: 5 * 1024 * 1024,  // 5MB
                allowedMimeTypes: ['image/jpeg', 'image/png', 'image/gif'],
                allowedExtensions: ['jpg', 'jpeg', 'png', 'gif']
            );

            if (!$isValid) {
                echo "File validation failed";
                return;
            }

            // Move to permanent location
            $uploadDir = __DIR__ . '/../../public/uploads/images';
            $newName = date('Ymd_His') . '_' . $fileUpload->getName();
            
            $moved = $fileUpload->move($uploadDir, $newName);

            echo "File uploaded successfully: " . $moved->getPathname();

        } catch (Exception $e) {
            echo "Upload error: " . $e->getMessage();
        }
    }
}
```

### Document Upload

```php
public function uploadDocument()
{
    $fileUpload = new FileUpload($_FILES['document']);

    $isValid = $fileUpload->isValid(
        maxSize: 20 * 1024 * 1024,  // 20MB for documents
        allowedMimeTypes: [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ],
        allowedExtensions: ['pdf', 'doc', 'docx']
    );

    if ($isValid) {
        $moved = $fileUpload->move('/uploads/documents');
        // Store path in database
        $this->documentRepository->save([
            'filename' => $moved->getName(),
            'path' => $moved->getPathname(),
            'mime_type' => $moved->getMimeType(),
            'size' => $moved->getSize(),
        ]);
    }
}
```

### Profile Picture Upload

```php
public function uploadProfilePicture()
{
    $fileUpload = new FileUpload($_FILES['profile_pic']);

    $isValid = $fileUpload->isValid(
        maxSize: 3 * 1024 * 1024,  // 3MB
        allowedMimeTypes: ['image/jpeg', 'image/png'],
        allowedExtensions: ['jpg', 'jpeg', 'png']
    );

    if (!$isValid) {
        return [
            'success' => false,
            'error' => 'Invalid image file'
        ];
    }

    try {
        $uploadDir = '/uploads/profiles';
        $filename = 'profile_' . auth()->user()->id . '.' . 
                    pathinfo($fileUpload->getName(), PATHINFO_EXTENSION);
        
        $moved = $fileUpload->move($uploadDir, $filename);

        // Delete old profile picture if exists
        $oldPath = auth()->user()->profile_picture_path;
        if ($oldPath && file_exists($oldPath)) {
            unlink($oldPath);
        }

        // Update user profile
        auth()->user()->update(['profile_picture_path' => $moved->getPathname()]);

        return ['success' => true, 'path' => $moved->getPathname()];

    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
```

---

## Test Cases

### Test 1: Move File Without Rename

Tests basic file movement functionality preserving original filename.

```php
public function testMoveFileWithoutRename(): void
{
    $tmpFile = $this->testDir . '/original.txt';
    file_put_contents($tmpFile, 'content');
    
    $fileData = [
        'name' => 'original.txt',
        'type' => 'text/plain',
        'tmp_name' => $tmpFile,
        'size' => 7,
        'error' => UPLOAD_ERR_OK,
    ];
    
    $fileUpload = new FileUpload($fileData, $this->fileSystem);
    $fileUpload->isValid(1000, ['text/plain']);
    
    $movedFile = $fileUpload->move($this->uploadDir);
    
    $this->assertFileExists($this->uploadDir . '/original.txt');
}
```

### Test 2: Move Throws Exception When File Not Valid

Tests that moving invalid files raises exception.

```php
public function testMoveThrowsExceptionWhenFileNotValid(): void
{
    $fileUpload = new FileUpload($fileData, $this->fileSystem);
    // Don't call isValid() or call it with restrictive params
    
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('The file is not valid');
    
    $fileUpload->move($this->uploadDir);
}
```

### Test 3: isValid Returns False For Wrong MIME Type

Tests MIME type validation.

```php
public function testIsValidReturnsFalseForWrongMimeType(): void
{
    $fileUpload = new FileUpload($fileData, $this->fileSystem);
    
    // File is text/plain but we only allow image/jpeg
    $this->assertFalse($fileUpload->isValid(1000, ['image/jpeg']));
}
```

### Test 4: isValid Returns False For Oversized File

Tests file size validation.

```php
public function testIsValidReturnsFalseForOversizedFile(): void
{
    $fileData = [
        'name' => 'test.txt',
        'type' => 'text/plain',
        'tmp_name' => $tmpFile,
        'size' => 1000,  // 1000 bytes
        'error' => UPLOAD_ERR_OK,
    ];
    
    $fileUpload = new FileUpload($fileData, $this->fileSystem);
    
    // File is larger than max size of 500
    $this->assertFalse($fileUpload->isValid(500, ['text/plain']));
}
```

### Test 5: Rejects Spoofed MIME Type

Tests attack prevention when PHP file is disguised as image.

```php
public function testRejectsSpoofedMimeType(): void
{
    // Create a PHP file disguised as an image
    $phpFile = $this->testDir . '/malicious.jpg';
    file_put_contents($phpFile, '<?php system($_GET["cmd"]); ?>');
    
    $fileData = [
        'name' => 'malicious.jpg',
        'type' => 'image/jpeg',  // Fake MIME type (spoofed by attacker)
        'tmp_name' => $phpFile,
        'size' => filesize($phpFile),
        'error' => UPLOAD_ERR_OK,
    ];
    
    $fileUpload = new FileUpload($fileData, $this->fileSystem);
    
    // Should reject because real MIME type is text/x-php, not image/jpeg
    $isValid = $fileUpload->isValid(
        maxSize: 1024 * 1024,
        allowedMimeTypes: ['image/jpeg', 'image/png']
    );
    
    $this->assertFalse($isValid, 'Should reject PHP file with spoofed image MIME type');
}
```

### Test 6: Move File Successfully

Tests complete file movement workflow with rename.

```php
public function testMoveFileSuccessfully(): void
{
    // Create a temporary test file
    $tmpFile = $this->testDir . '/test.txt';
    file_put_contents($tmpFile, 'test content');
    
    $fileData = [
        'name' => 'test.txt',
        'type' => 'text/plain',
        'tmp_name' => $tmpFile,
        'size' => 12,
        'error' => UPLOAD_ERR_OK,
    ];
    $fileUpload = new FileUpload($fileData, new TestFileSystem());
    
    // Validate the file first
    $this->assertTrue($fileUpload->isValid(1000, ['text/plain']));
    
    // Move the file with new name
    $movedFile = $fileUpload->move($this->uploadDir, 'moved.txt');
    
    // Assertions
    $this->assertInstanceOf(FileUpload::class, $movedFile);
    $this->assertFileExists($this->uploadDir . '/moved.txt');
    $this->assertEquals('test content', file_get_contents($this->uploadDir . '/moved.txt'));
    $this->assertFileDoesNotExist($tmpFile);  // Original should be gone
}
```

---

## Security Best Practices

### 1. Always Validate Before Moving

```php
// NEVER do this:
$moved = $fileUpload->move($dir);  // ❌ Not validated!

// Always validate first:
if ($fileUpload->isValid(maxSize, types, extensions)) {
    $moved = $fileUpload->move($dir);  // ✅ Safe
}
```

### 2. Use Restrictive MIME Type Whitelists

```php
// ✅ Good - Specific MIME types
$isValid = $fileUpload->isValid(
    maxSize: 5000000,
    allowedMimeTypes: ['image/jpeg', 'image/png']
);

// ❌ Bad - Too permissive
$isValid = $fileUpload->isValid(
    maxSize: 5000000,
    allowedMimeTypes: []  // Allows any MIME type!
);
```

### 3. Randomize Filenames

```php
// ❌ Bad - Preserves original filename
$moved = $fileUpload->move($dir);

// ✅ Good - Use random/hashed filename
$newName = md5(uniqid() . time()) . '.' . 
           pathinfo($fileUpload->getName(), PATHINFO_EXTENSION);
$moved = $fileUpload->move($dir, $newName);
```

### 4. Validate File Size Appropriately

```php
// ✅ Profile pictures: small limit
$isValid = $fileUpload->isValid(2 * 1024 * 1024);  // 2MB

// ✅ Documents: larger limit
$isValid = $fileUpload->isValid(20 * 1024 * 1024);  // 20MB

// ✅ Videos: much larger limit
$isValid = $fileUpload->isValid(500 * 1024 * 1024);  // 500MB
```

### 5. Store Uploads Outside Web Root

```php
// ✅ Good - Outside public directory
$uploadDir = __DIR__ . '/../../storage/uploads';

// ❌ Risky - Inside web root (scripts might execute)
$uploadDir = __DIR__ . '/../../public/uploads';
```

### 6. Use Extension Whitelist

```php
// ✅ Enforce specific extensions
$isValid = $fileUpload->isValid(
    maxSize: 1000000,
    allowedMimeTypes: ['image/jpeg', 'image/png'],
    allowedExtensions: ['jpg', 'jpeg', 'png']  // Extension whitelist
);
```

---

## Related Classes

- `FileSystem`: Default file system implementation
- `FileSystemInterface`: Interface for file system operations
- `FileFormator`: File formatting utilities

---

## Summary

`FileUpload` provides a production-ready, security-focused solution for handling file uploads in the smallAPIServer framework. It implements multiple layers of validation and protection against common file upload attacks, making it safe to use with user-supplied files.

Key advantages:
- **Real MIME type detection** prevents spoofing attacks
- **Double extension prevention** blocks dangerous files
- **Image validation** ensures image files are legitimate
- **Flexible validation** with customizable rules
- **Comprehensive error handling** with readable messages
- **Safe file movement** with permission management
