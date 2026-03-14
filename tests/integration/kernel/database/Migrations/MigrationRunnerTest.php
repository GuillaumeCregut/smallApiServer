<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Exceptions\KernelException;
use App\Kernel\Connector\Interfaces\ConnectorInterface;
use App\Kernel\Connector\Utils\Migration\MigrationRunner;

class MigrationRunnerTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        // Isolated temp migrations directory per test
        $this->tempDir = sys_get_temp_dir() . '/migrations_test_' . uniqid();
        mkdir($this->tempDir . '/migrations', 0755, true);
    }

    protected function tearDown(): void
    {
        // Clean up generated migration files
        foreach (glob($this->tempDir . '/migrations/*.php') as $file) {
            unlink($file);
        }
        rmdir($this->tempDir . '/migrations');
        rmdir($this->tempDir);
    }

    private function makeConnector(
        array $fetchQueryReturn    = [],
        mixed $fetchQueryOnceReturn = null,
        mixed $executeQueryReturn  = true
    ): ConnectorInterface {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')->willReturn($fetchQueryReturn);
        $connector->method('fetchQueryOnce')->willReturn($fetchQueryOnceReturn);
        $connector->method('executeQuery')->willReturn($executeQueryReturn);
        return $connector;
    }

    private function writeMigrationFile(string $version, bool $failUp = false, bool $failDown = false): string
    {
        $upBody   = $failUp
            ? 'throw new \RuntimeException("up failed");'
            : '$connector->executeQuery("ALTER TABLE users ADD COLUMN test VARCHAR(255) NOT NULL;");';
        $downBody = $failDown
            ? 'throw new \RuntimeException("down failed");'
            : '$connector->executeQuery("ALTER TABLE users DROP COLUMN test;");';

        $content = <<<PHP
<?php
use App\Kernel\Connector\Interfaces\ConnectorInterface;

use App\Kernel\Connector\Interfaces\MigrationInterface;

class Version{$version} implements MigrationInterface
{
    public function up(ConnectorInterface \$connector): void
    {
        {$upBody}
    }

    public function down(ConnectorInterface \$connector): void
    {
        {$downBody}
    }
}
PHP;
        $path = "{$this->tempDir}/migrations/Version{$version}.php";
        file_put_contents($path, $content);
        return $path;
    }

    // -------------------------------------------------------------------------
    // status
    // -------------------------------------------------------------------------

    public function testStatusReturnsEmptyWhenNoMigrationFiles(): void
    {
        $connector = $this->makeConnector();
        $runner    = new MigrationRunner($connector, $this->tempDir);

        $this->assertEmpty($runner->status());
    }

    public function testStatusReturnsPendingForNewMigration(): void
    {
        $this->writeMigrationFile('20260101000010');

        $connector = $this->makeConnector(fetchQueryReturn: []);
        $runner    = new MigrationRunner($connector, $this->tempDir);
        $status    = $runner->status();

        $this->assertArrayHasKey('20260101000010', $status);
        $this->assertEquals('pending', $status['20260101000010']);
    }

    public function testStatusReturnsExecutedForAlreadyRunMigration(): void
    {
        $this->writeMigrationFile('20260101000020');

        $connector = $this->makeConnector(fetchQueryReturn: [
            ['version' => '20260101000020']
        ]);
        $runner = new MigrationRunner($connector, $this->tempDir);
        $status = $runner->status();

        $this->assertEquals('executed', $status['20260101000020']);
    }

    public function testStatusShowsMixedExecutedAndPending(): void
    {
        $this->writeMigrationFile('20260101000030');
        $this->writeMigrationFile('20260101000040');

        $connector = $this->makeConnector(fetchQueryReturn: [
            ['version' => '20260101000030']
        ]);
        $runner = new MigrationRunner($connector, $this->tempDir);
        $status = $runner->status();

        $this->assertEquals('executed', $status['20260101000030']);
        $this->assertEquals('pending',  $status['20260101000040']);
    }

    // -------------------------------------------------------------------------
    // migrate
    // -------------------------------------------------------------------------

    public function testMigrateReturnsEmptyWhenNoPendingMigrations(): void
    {
        $connector = $this->makeConnector();
        $runner    = new MigrationRunner($connector, $this->tempDir);

        $this->assertEmpty($runner->migrate());
    }


    /* 
    public function testMigrateDebug(): void
    {
        $path = $this->writeMigrationFile('20260101000050');

        // Check file exists
        var_dump('file exists: ' . ($path && file_exists($path) ? 'YES' : 'NO'));
        var_dump('migrations dir: ' . $this->tempDir . '/migrations');
        var_dump('glob result: ', glob($this->tempDir . '/migrations/*.php'));

        $connector = $this->createMock(ConnectorInterface::class);
        $connector->method('fetchQuery')->willReturn([]);
        $connector->method('fetchQueryOnce')->willReturn(null);
        $connector->method('executeQuery')->willReturn(true);

        $runner = new MigrationRunner($connector, $this->tempDir);

        // Check class exists before migrate
        var_dump('class exists before: ' . (class_exists('Version20260101000050') ? 'YES' : 'NO'));

        try {
            $executed = $runner->migrate();
            var_dump('executed: ', $executed);
        } catch (\Throwable $e) {
            var_dump('EXCEPTION: ' . get_class($e) . ': ' . $e->getMessage());
        }

        $this->assertTrue(true); // just to not fail
    }


     */
    
    public function testMigrateRunsPendingMigrations(): void
    {
        $this->writeMigrationFile('20260101000050');

        $connector = $this->createMock(ConnectorInterface::class);
        $connector->method('fetchQuery')->willReturn([]);
        $connector->method('fetchQueryOnce')->willReturn(null);
        $connector->method('executeQuery')->willReturn(true);
        $connector->expects($this->once())->method('startTransac');
        $connector->expects($this->once())->method('commitTransac');

        $runner   = new MigrationRunner($connector, $this->tempDir);
        $executed = $runner->migrate();

        $this->assertContains('20260101000050', $executed);
    }

    public function testMigrateSkipsAlreadyExecutedMigrations(): void
    {
        $this->writeMigrationFile('20260101000060');

        $connector = $this->makeConnector(fetchQueryReturn: [
            ['version' => '20260101000060']
        ]);
        $runner   = new MigrationRunner($connector, $this->tempDir);
        $executed = $runner->migrate();

        $this->assertEmpty($executed);
    }

    public function testMigrateRunsMultiplePendingInOrder(): void
    {
        $this->writeMigrationFile('20260101000070');
        $this->writeMigrationFile('20260101000080');
        $this->writeMigrationFile('20260101000090');

        $executedVersions = [];

        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')->willReturn([]);
        $connector->method('fetchQueryOnce')->willReturn(null);
        $connector->method('executeQuery')
            ->willReturnCallback(function (string $sql, array $params = []) use (&$executedVersions) {
                if (stripos($sql, 'INSERT INTO migrations') !== false) {
                    $executedVersions[] = $params['version'];
                }
                return true;
            });

        $runner   = new MigrationRunner($connector, $this->tempDir);
        $executed = $runner->migrate();

        $this->assertCount(3, $executed);
        $this->assertEquals(['20260101000070', '20260101000080', '20260101000090'], $executed);
    }

    public function testMigrateRollsBackOnFailure(): void
    {
        $this->writeMigrationFile('20260101000100', failUp: true);

        $connector = $this->createMock(ConnectorInterface::class);
        $connector->method('fetchQuery')->willReturn([]);
        $connector->method('fetchQueryOnce')->willReturn(null);
        $connector->method('executeQuery')->willReturn(true);
        $connector->expects($this->once())->method('startTransac');
        $connector->expects($this->once())->method('rollBack');
        $connector->expects($this->never())->method('commitTransac');

        $runner = new MigrationRunner($connector, $this->tempDir);

        $this->expectException(KernelException::class);
        $runner->migrate();
    }

    // -------------------------------------------------------------------------
    // rollback
    // -------------------------------------------------------------------------

    public function testRollbackReturnsNullWhenNothingExecuted(): void
    {
        $connector = $this->makeConnector(fetchQueryOnceReturn: null);
        $runner    = new MigrationRunner($connector, $this->tempDir);

        $this->assertNull($runner->rollback());
    }

    public function testRollbackRunsDownOnLastMigration(): void
    {
        $this->writeMigrationFile('20260101000101');

        $connector = $this->createMock(ConnectorInterface::class);
        $connector->method('fetchQuery')->willReturn([]);
        $connector->method('fetchQueryOnce')->willReturn(['version' => '20260101000101']);
        $connector->method('executeQuery')->willReturn(true);
        $connector->expects($this->once())->method('startTransac');
        $connector->expects($this->once())->method('commitTransac');

        $runner  = new MigrationRunner($connector, $this->tempDir);
        $version = $runner->rollback();

        $this->assertEquals('20260101000101', $version);
    }

    public function testRollbackRollsBackTransactionOnFailure(): void
    {
        $this->writeMigrationFile('20260101000102', failDown: true);

        $connector = $this->createMock(ConnectorInterface::class);
        $connector->method('fetchQuery')->willReturn([]);
        $connector->method('fetchQueryOnce')->willReturn(['version' => '20260101000102']);
        $connector->method('executeQuery')->willReturn(true);
        $connector->expects($this->once())->method('startTransac');
        $connector->expects($this->once())->method('rollBack');
        $connector->expects($this->never())->method('commitTransac');

        $runner = new MigrationRunner($connector, $this->tempDir);

        $this->expectException(KernelException::class);
        $runner->rollback();
    }

    public function testRollbackThrowsWhenMigrationFileNotFound(): void
    {
        // fetchQueryOnce returns a version but the file doesn't exist
        $connector = $this->makeConnector(fetchQueryOnceReturn: ['version' => '99991231235959']);
        $runner    = new MigrationRunner($connector, $this->tempDir);

        $this->expectException(KernelException::class);
        $runner->rollback();
    }
}
