<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Utils\Migration\MigrationWriter;

class MigrationWriterTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/migration_writer_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        // Clean up generated files and directory
        if (is_dir($this->tempDir . '/migrations')) {
            foreach (glob($this->tempDir . '/migrations/*.php') as $file) {
                unlink($file);
            }
            rmdir($this->tempDir . '/migrations');
        }
        rmdir($this->tempDir);
    }

    public function testWriteReturnsNullWhenDiffIsEmpty(): void
    {
        $writer = new MigrationWriter($this->tempDir);
        $result = $writer->write($this->emptyDiff());

        $this->assertNull($result);
    }

    public function testWriteCreatesNoFileWhenDiffIsEmpty(): void
    {
        $writer = new MigrationWriter($this->tempDir);
        $writer->write($this->emptyDiff());

        $files = glob($this->tempDir . '/migrations/*.php') ?: [];
        $this->assertEmpty($files);
    }

    public function testWriteReturnsFilePathOnSuccess(): void
    {
        $writer = new MigrationWriter($this->tempDir);
        $path   = $writer->write($this->diffWithSafeSQL());

        $this->assertNotNull($path);
        $this->assertFileExists($path);
    }

    public function testWrittenFileIsInMigrationsDirectory(): void
    {
        $writer = new MigrationWriter($this->tempDir);
        $path   = $writer->write($this->diffWithSafeSQL());

        $this->assertStringStartsWith($this->tempDir . '/migrations/', $path);
    }

    public function testWrittenFileNameMatchesVersionFormat(): void
    {
        $writer   = new MigrationWriter($this->tempDir);
        $path     = $writer->write($this->diffWithSafeSQL());
        $filename = basename($path, '.php');

        // Version + 14 digit timestamp YYYYmmddHHiiss
        $this->assertMatchesRegularExpression('/^Version\d{14}$/', $filename);
    }

    public function testWriteCreatesMigrationsDirectoryIfNotExists(): void
    {
        // tempDir exists but migrations/ does not
        $this->assertDirectoryDoesNotExist($this->tempDir . '/migrations');

        $writer = new MigrationWriter($this->tempDir);
        $writer->write($this->diffWithSafeSQL());

        $this->assertDirectoryExists($this->tempDir . '/migrations');
    }

    public function testWrittenFileContainsCorrectClassName(): void
    {
        $writer   = new MigrationWriter($this->tempDir);
        $path     = $writer->write($this->diffWithSafeSQL());
        $filename = basename($path, '.php');
        $content  = file_get_contents($path);
 
        $this->assertStringContainsString("class {$filename}", $content);
    }

    public function testWrittenFileImplementsMigrationInterface(): void
    {
        $writer  = new MigrationWriter($this->tempDir);
        $path    = $writer->write($this->diffWithSafeSQL());
        $content = file_get_contents($path);
 
        $this->assertStringContainsString('implements MigrationInterface', $content);
    }

    public function testWrittenFileContainsUpMethod(): void
    {
        $writer  = new MigrationWriter($this->tempDir);
        $path    = $writer->write($this->diffWithSafeSQL());
        $content = file_get_contents($path);
 
        $this->assertStringContainsString('public function up(ConnectorInterface $connector): void', $content);
    }

    public function testWrittenFileContainsDownMethod(): void
    {
        $writer  = new MigrationWriter($this->tempDir);
        $path    = $writer->write($this->diffWithSafeSQL());
        $content = file_get_contents($path);
 
        $this->assertStringContainsString('public function down(ConnectorInterface $connector): void', $content);
    }

    public function testUpMethodContainsSafeStatements(): void
    {
        $writer  = new MigrationWriter($this->tempDir);
        $path    = $writer->write($this->diffWithSafeSQL());
        $content = file_get_contents($path);
 
        $this->assertStringContainsString(
            'ALTER TABLE `users` ADD COLUMN `phone` VARCHAR(255) NULL;',
            $content
        );
    }

    public function testUpMethodContainsDestructiveStatements(): void
    {
        $writer  = new MigrationWriter($this->tempDir);
        $path    = $writer->write($this->diffWithBothSQL());
        $content = file_get_contents($path);
 
        $this->assertStringContainsString('DROP TABLE `obsolete`;', $content);
    }

    public function testDownMethodContainsTodoComment(): void
    {
        $writer  = new MigrationWriter($this->tempDir);
        $path    = $writer->write($this->diffWithSafeSQL());
        $content = file_get_contents($path);
 
        $this->assertStringContainsString('TODO: add rollback statements', $content);
    }

    public function testWrittenFileIsValidPHP(): void
    {
        $writer = new MigrationWriter($this->tempDir);
        $path   = $writer->write($this->diffWithSafeSQL());
 
        // php -l returns exit code 0 for valid syntax
        exec("php -l {$path}", $output, $exitCode);
        $this->assertEquals(0, $exitCode, implode("\n", $output));
    }

    private function emptyDiff(): array
    {
        return ['safe' => [], 'destructive' => []];
    }

    private function diffWithSafeSQL(): array
    {
        return [
            'safe'        => ["ALTER TABLE `users` ADD COLUMN `phone` VARCHAR(255) NULL;"],
            'destructive' => [],
        ];
    }

    private function diffWithBothSQL(): array
    {
        return [
            'safe'        => ["ALTER TABLE `users` ADD COLUMN `phone` VARCHAR(255) NULL;"],
            'destructive' => ["DROP TABLE `obsolete`;"],
        ];
    }
}
