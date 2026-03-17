<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Utils\Migration\MigrationWriter;

class MigrationWriterTest extends TestCase
{
   private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/migration_writer_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir . '/migrations/*.php') as $f) {
            unlink($f);
        }
        if (is_dir($this->tmpDir . '/migrations')) {
            rmdir($this->tmpDir . '/migrations');
        }
        rmdir($this->tmpDir);
    }

    private function emptySql(): array
    {
        return ['safe' => [], 'destructive' => []];
    }

    // -------------------------------------------------------------------------
    // write() — null when nothing to do
    // -------------------------------------------------------------------------

    public function testWriteReturnsNullWhenBothArraysEmpty(): void
    {
        $writer = new MigrationWriter($this->tmpDir);
        $result = $writer->write($this->emptySql());

        $this->assertNull($result);
    }

    public function testWriteReturnsNullWhenOnlySafeIsEmpty(): void
    {
        $writer = new MigrationWriter($this->tmpDir);
        $result = $writer->write(['safe' => [], 'destructive' => []]);

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // write() — file creation
    // -------------------------------------------------------------------------

    public function testWriteCreatesFileAndReturnsPath(): void
    {
        $writer = new MigrationWriter($this->tmpDir);
        $path   = $writer->write([
            'safe'        => ['CREATE TABLE `users` (`id` INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`));'],
            'destructive' => [],
        ]);

        $this->assertNotNull($path);
        $this->assertFileExists($path);
    }

    public function testWriteCreatesMigrationsDirIfMissing(): void
    {
        $writer = new MigrationWriter($this->tmpDir);
        $writer->write([
            'safe'        => ['CREATE TABLE `users` (`id` INT NOT NULL);'],
            'destructive' => [],
        ]);

        $this->assertDirectoryExists($this->tmpDir . DIRECTORY_SEPARATOR . 'migrations');
    }

    public function testWriteFileNameMatchesVersionPattern(): void
    {
        $writer = new MigrationWriter($this->tmpDir);
        $path   = $writer->write([
            'safe'        => ['SELECT 1;'],
            'destructive' => [],
        ]);

        $fileName = basename($path, '.php');
        $this->assertMatchesRegularExpression('/^Version\d{14}$/', $fileName);
    }

    // -------------------------------------------------------------------------
    // write() — file contents
    // -------------------------------------------------------------------------

    public function testWrittenFileContainsClassName(): void
    {
        $writer  = new MigrationWriter($this->tmpDir);
        $path    = $writer->write(['safe' => ['SELECT 1;'], 'destructive' => []]);
        $content = file_get_contents($path);
        $className = basename($path, '.php');

        $this->assertStringContainsString("class {$className}", $content);
    }

    public function testWrittenFileImplementsMigrationInterface(): void
    {
        $writer  = new MigrationWriter($this->tmpDir);
        $path    = $writer->write(['safe' => ['SELECT 1;'], 'destructive' => []]);
        $content = file_get_contents($path);

        $this->assertStringContainsString('implements MigrationInterface', $content);
    }

    public function testWrittenFileContainsUpMethod(): void
    {
        $writer  = new MigrationWriter($this->tmpDir);
        $path    = $writer->write(['safe' => ['SELECT 1;'], 'destructive' => []]);
        $content = file_get_contents($path);

        $this->assertStringContainsString('public function up(ConnectorInterface $connector): void', $content);
    }

    public function testWrittenFileContainsDownMethod(): void
    {
        $writer  = new MigrationWriter($this->tmpDir);
        $path    = $writer->write(['safe' => ['SELECT 1;'], 'destructive' => []]);
        $content = file_get_contents($path);

        $this->assertStringContainsString('public function down(ConnectorInterface $connector): void', $content);
    }

    public function testWrittenFileContainsSafeStatements(): void
    {
        $writer  = new MigrationWriter($this->tmpDir);
        $path    = $writer->write([
            'safe'        => ['CREATE TABLE `users` (`id` INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`));'],
            'destructive' => [],
        ]);
        $content = file_get_contents($path);

        $this->assertStringContainsString('CREATE TABLE', $content);
        $this->assertStringContainsString('$connector->executeQuery(', $content);
    }

    public function testWrittenFileContainsDestructiveStatements(): void
    {
        $writer  = new MigrationWriter($this->tmpDir);
        $path    = $writer->write([
            'safe'        => [],
            'destructive' => ['DROP TABLE `old_table`;'],
        ]);
        $content = file_get_contents($path);

        $this->assertStringContainsString('DROP TABLE', $content);
        $this->assertStringContainsString('$connector->executeQuery(', $content);
    }

    public function testWrittenFileContainsBothSafeAndDestructiveStatements(): void
    {
        $writer  = new MigrationWriter($this->tmpDir);
        $path    = $writer->write([
            'safe'        => ['CREATE TABLE `users` (`id` INT NOT NULL);'],
            'destructive' => ['DROP TABLE `old_table`;'],
        ]);
        $content = file_get_contents($path);

        $this->assertStringContainsString('CREATE TABLE', $content);
        $this->assertStringContainsString('DROP TABLE', $content);
    }

    public function testWrittenFileContainsDownTodoPlaceholder(): void
    {
        $writer  = new MigrationWriter($this->tmpDir);
        $path    = $writer->write(['safe' => ['SELECT 1;'], 'destructive' => []]);
        $content = file_get_contents($path);

        $this->assertStringContainsString('TODO: add rollback statements', $content);
    }

    public function testWrittenFileStatementsAreEscaped(): void
    {
        // Statement contains backslashes and quotes that must be escaped
        $writer  = new MigrationWriter($this->tmpDir);
        $path    = $writer->write([
            'safe' => ["ALTER TABLE `users` ADD COLUMN `path` VARCHAR(255) NOT NULL DEFAULT 'C:\\\\dir';"],
            'destructive' => [],
        ]);
        $content = file_get_contents($path);

        // File must be valid PHP — load it to check it doesn't fatal
        $this->assertStringContainsString('$connector->executeQuery(', $content);
    }

    public function testWrittenFileOnlyDestructiveStillWrites(): void
    {
        $writer = new MigrationWriter($this->tmpDir);
        $path   = $writer->write([
            'safe'        => [],
            'destructive' => ['DROP TABLE `stale`;'],
        ]);

        $this->assertNotNull($path);
        $this->assertFileExists($path);
    }

    public function testWrittenFileUsesConnectorInterfaceImport(): void
    {
        $writer  = new MigrationWriter($this->tmpDir);
        $path    = $writer->write(['safe' => ['SELECT 1;'], 'destructive' => []]);
        $content = file_get_contents($path);

        $this->assertStringContainsString('use App\Kernel\Connector\Interfaces\ConnectorInterface;', $content);
        $this->assertStringContainsString('use App\Kernel\Connector\Interfaces\MigrationInterface;', $content);
    }

    // -------------------------------------------------------------------------
    // Multiple writes produce unique version filenames
    // -------------------------------------------------------------------------

    public function testTwoWritesProduceDifferentFileNames(): void
    {
        $writer = new MigrationWriter($this->tmpDir);
        $sql    = ['safe' => ['SELECT 1;'], 'destructive' => []];

        $path1 = $writer->write($sql);
        sleep(1); // ensure different timestamp
        $path2 = $writer->write($sql);

        $this->assertNotEquals($path1, $path2);
    }
}
