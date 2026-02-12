<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Files\FileUpload;

include('TestFileSystem.php');

class FileUploadTest extends TestCase
{
    private string $testDir;
    private string $uploadDir;
    private TestFileSystem $fileSystem;
    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/file_upload_test_' . uniqid();
        $this->uploadDir = $this->testDir . '/uploads';
        mkdir($this->testDir, 0777, true);
        $this->fileSystem = new TestFileSystem();
    }

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
    
    public function testMoveThrowsExceptionWhenFileNotValid(): void
    {
        $tmpFile = $this->testDir . '/test.txt';
        file_put_contents($tmpFile, 'test');
        
        $fileData = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => $tmpFile,
            'size' => 4,
            'error' => UPLOAD_ERR_OK,
        ];
        
        $fileUpload = new FileUpload($fileData, $this->fileSystem);
        // Don't call isValid() or call it with restrictive params
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The file is not valid');
        
        $fileUpload->move($this->uploadDir);
    }
    
    public function testIsValidReturnsFalseForWrongMimeType(): void
    {
        $tmpFile = $this->testDir . '/test.txt';
        file_put_contents($tmpFile, 'test');
        
        $fileData = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => $tmpFile,
            'size' => 4,
            'error' => UPLOAD_ERR_OK,
        ];
        
        $fileUpload = new FileUpload($fileData, $this->fileSystem);
        
        $this->assertFalse($fileUpload->isValid(1000, ['image/jpeg']));
    }
    
    public function testIsValidReturnsFalseForOversizedFile(): void
    {
        $tmpFile = $this->testDir . '/test.txt';
        file_put_contents($tmpFile, 'test');
        
        $fileData = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => $tmpFile,
            'size' => 1000,
            'error' => UPLOAD_ERR_OK,
        ];
        
        $fileUpload = new FileUpload($fileData, $this->fileSystem);
        
        $this->assertFalse($fileUpload->isValid(500, ['text/plain']));
    }

    public function testRejectsSpoofedMimeType(): void
    {
        // Create a PHP file disguised as an image
        $phpFile = $this->testDir . '/malicious.jpg';
        file_put_contents($phpFile, '<?php system($_GET["cmd"]); ?>');
        
        $fileData = [
            'name' => 'malicious.jpg',
            'type' => 'image/jpeg', // Fake MIME type (spoofed by attacker)
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

    protected function tearDown(): void
    {
        $this->removeDirectory($this->testDir);
    }

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
        // Move the file
        $movedFile = $fileUpload->move($this->uploadDir, 'moved.txt');
        // Assertions
        $this->assertInstanceOf(FileUpload::class, $movedFile);
        $this->assertFileExists($this->uploadDir . '/moved.txt');
        $this->assertEquals('test content', file_get_contents($this->uploadDir . '/moved.txt'));
        $this->assertFileDoesNotExist($tmpFile); // Original should be gone
    }
    
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
