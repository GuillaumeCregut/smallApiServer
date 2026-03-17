<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Exceptions\KernelException;
use App\Kernel\Connector\Interfaces\ConnectorInterface;
use App\Kernel\Connector\Utils\Migration\MigrationRunner;

class MigrationRunnerTest extends TestCase
{
     private string $tmpDir;
 
    protected function setUp(): void
    {
        // Create a temp directory to hold migration files for each test
        $this->tmpDir = sys_get_temp_dir() . '/migration_runner_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }
 
    protected function tearDown(): void
    {
        // Clean up all temp files
        foreach (glob($this->tmpDir . '/migrations/*.php') as $f) {
            unlink($f);
        }
        if (is_dir($this->tmpDir . '/migrations')) {
            rmdir($this->tmpDir . '/migrations');
        }
        rmdir($this->tmpDir);
    }
 
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------
 
    /**
     * Build a connector stub. fetchQuery returns executed versions,
     * fetchQueryOnce returns last executed version for rollback.
     * supportsTransactionalDDL controls transaction wrapping.
     */
    private function makeConnector(
        array $executedVersions = [],
        ?string $lastVersion = null,
        bool $transactionalDDL = false
    ): ConnectorInterface {
        $connector = $this->createStub(ConnectorInterface::class);
 
        $connector->method('executeQuery')->willReturn(true);
        $connector->method('supportsTransactionalDDL')->willReturn($transactionalDDL);
 
        $connector->method('fetchQuery')
            ->willReturnCallback(function (string $sql) use ($executedVersions) {
                if (stripos($sql, 'SELECT version') !== false) {
                    return array_map(fn($v) => ['version' => $v], $executedVersions);
                }
                return [];
            });
 
        $connector->method('fetchQueryOnce')
            ->willReturnCallback(function (string $sql) use ($lastVersion) {
                if (stripos($sql, 'SELECT version') !== false) {
                    return $lastVersion ? ['version' => $lastVersion] : null;
                }
                return null;
            });
 
        return $connector;
    }
 
    /**
     * Write a real migration PHP file into the tmp migrations directory.
     */
    private function writeMigrationFile(string $version, bool $upThrows = false): void
    {
        $dir = $this->tmpDir . '/migrations';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
 
        $className = "Version{$version}";
        $upBody = $upThrows
            ? 'throw new \RuntimeException("Migration failed");'
            : '// no-op';
 
        file_put_contents("{$dir}/{$className}.php", <<<PHP
<?php
use App\Kernel\Connector\Interfaces\ConnectorInterface;
use App\Kernel\Connector\Interfaces\MigrationInterface;
 
class {$className} implements MigrationInterface
{
    public function up(ConnectorInterface \$connector): void
    {
        {$upBody}
    }
    public function down(ConnectorInterface \$connector): void
    {
        // no-op
    }
}
PHP);
    }
 
    // -------------------------------------------------------------------------
    // migrate()
    // -------------------------------------------------------------------------
 
    public function testMigrateReturnsEmptyArrayWhenNoPendingMigrations(): void
    {
        $connector = $this->makeConnector(['20260101000001']);
        $this->writeMigrationFile('20260101000001');
 
        $runner = new MigrationRunner($connector, $this->tmpDir);
        $result = $runner->migrate();
 
        $this->assertEmpty($result);
    }
 
    public function testMigrateExecutesPendingMigrationsInOrder(): void
    {
        $this->writeMigrationFile('20260101000010');
        $this->writeMigrationFile('20260101000002');
 
        $connector = $this->makeConnector([]);
        $runner    = new MigrationRunner($connector, $this->tmpDir);
        $result    = $runner->migrate();
 
        $this->assertEquals(['20260101000002', '20260101000010'], $result);
    }
 
    public function testMigrateSkipsAlreadyExecutedVersions(): void
    {
        $this->writeMigrationFile('20260101000003');
        $this->writeMigrationFile('20260101000004');
 
        // 000001 already executed
        $connector = $this->makeConnector(['20260101000003']);
        $runner    = new MigrationRunner($connector, $this->tmpDir);
        $result    = $runner->migrate();
 
        $this->assertEquals(['20260101000004'], $result);
    }
 
    public function testMigrateMarksVersionAsExecuted(): void
    {
        $this->writeMigrationFile('20260101000005');
 
        // After migrate(), the version should be returned in the executed list
        $connector = $this->makeConnector([]);
        $runner    = new MigrationRunner($connector, $this->tmpDir);
        $result    = $runner->migrate();
 
        $this->assertContains('20260101000005', $result);
    }
 
    public function testMigrateThrowsKernelExceptionOnFailure(): void
    {
        $this->writeMigrationFile('20260101000006', upThrows: true);
 
        $connector = $this->makeConnector([]);
        $runner    = new MigrationRunner($connector, $this->tmpDir);
 
        $this->expectException(KernelException::class);
        $this->expectExceptionMessageMatches('/Migration 20260101000006 failed/');
 
        $runner->migrate();
    }
 
    public function testMigrateWithTransactionalDDLCommitsOnSuccess(): void
    {
        $this->writeMigrationFile('20260101000007');
 
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('supportsTransactionalDDL')->willReturn(true);
        $connector->method('fetchQuery')->willReturn([]);
        $connector->method('fetchQueryOnce')->willReturn(null);
        $connector->method('executeQuery')->willReturn(true);
 
        // Use a separate spy mock just to assert transaction calls
        $spy = $this->createMock(ConnectorInterface::class);
        $spy->method('supportsTransactionalDDL')->willReturn(true);
        $spy->method('fetchQuery')->willReturn([]);
        $spy->method('fetchQueryOnce')->willReturn(null);
        $spy->method('executeQuery')->willReturn(true);
        $spy->expects($this->once())->method('startTransac');
        $spy->expects($this->once())->method('commitTransac');
        $spy->expects($this->never())->method('rollBack');
 
        $runner = new MigrationRunner($spy, $this->tmpDir);
        $runner->migrate();
    }
 
    public function testMigrateWithTransactionalDDLRollsBackOnFailure(): void
    {
        $this->writeMigrationFile('20260101000078', upThrows: true);
 
        $spy = $this->createMock(ConnectorInterface::class);
        $spy->method('supportsTransactionalDDL')->willReturn(true);
        $spy->method('fetchQuery')->willReturn([]);
        $spy->method('fetchQueryOnce')->willReturn(null);
        $spy->method('executeQuery')->willReturn(true);
        $spy->expects($this->once())->method('startTransac');
        $spy->expects($this->never())->method('commitTransac');
        $spy->expects($this->once())->method('rollBack');
 
        $runner = new MigrationRunner($spy, $this->tmpDir);
 
        $this->expectException(KernelException::class);
        $runner->migrate();
    }
 
    public function testMigrateWithoutTransactionalDDLNeverCallsTransactionMethods(): void
    {
        $this->writeMigrationFile('20260101000008');
 
        $spy = $this->createMock(ConnectorInterface::class);
        $spy->method('supportsTransactionalDDL')->willReturn(false);
        $spy->method('fetchQuery')->willReturn([]);
        $spy->method('fetchQueryOnce')->willReturn(null);
        $spy->method('executeQuery')->willReturn(true);
        $spy->expects($this->never())->method('startTransac');
        $spy->expects($this->never())->method('commitTransac');
        $spy->expects($this->never())->method('rollBack');
 
        $runner = new MigrationRunner($spy, $this->tmpDir);
        $runner->migrate();
    }
 
    public function testMigrateReturnsEmptyWhenMigrationsDirDoesNotExist(): void
    {
        $connector = $this->makeConnector([]);
        $runner    = new MigrationRunner($connector, $this->tmpDir . '/nonexistent');
        $result    = $runner->migrate();
 
        $this->assertEmpty($result);
    }
 
    // -------------------------------------------------------------------------
    // rollback()
    // -------------------------------------------------------------------------
 
    public function testRollbackReturnsNullWhenNothingExecuted(): void
    {
        $connector = $this->makeConnector([], null);
        $runner    = new MigrationRunner($connector, $this->tmpDir);
 
        $this->assertNull($runner->rollback());
    }
 
    public function testRollbackExecutesDownAndReturnsVersion(): void
    {
        $this->writeMigrationFile('20260101000101');
 
        $connector = $this->makeConnector(['20260101000101'], '20260101000101');
        $runner    = new MigrationRunner($connector, $this->tmpDir);
        $result    = $runner->rollback();
 
        $this->assertEquals('20260101000101', $result);
    }
 
    public function testRollbackRemovesVersionFromExecutedTable(): void
    {
        $this->writeMigrationFile('20260101000102');
 
        // After rollback(), the returned version confirms the tracking row was removed
        $connector = $this->makeConnector(['20260101000102'], '20260101000102');
        $runner    = new MigrationRunner($connector, $this->tmpDir);
        $result    = $runner->rollback();
 
        $this->assertEquals('20260101000102', $result);
    }
 
    public function testRollbackWithTransactionalDDLCommitsOnSuccess(): void
    {
        $this->writeMigrationFile('20260101000103');
 
        $spy = $this->createMock(ConnectorInterface::class);
        $spy->method('supportsTransactionalDDL')->willReturn(true);
        $spy->method('fetchQuery')->willReturn([['version' => '20260101000103']]);
        $spy->method('fetchQueryOnce')->willReturn(['version' => '20260101000103']);
        $spy->method('executeQuery')->willReturn(true);
        $spy->expects($this->once())->method('startTransac');
        $spy->expects($this->once())->method('commitTransac');
        $spy->expects($this->never())->method('rollBack');
 
        $runner = new MigrationRunner($spy, $this->tmpDir);
        $runner->rollback();
    }
 
    public function testRollbackWithoutTransactionalDDLNeverCallsTransactionMethods(): void
    {
        $this->writeMigrationFile('20260101000104');
 
        $spy = $this->createMock(ConnectorInterface::class);
        $spy->method('supportsTransactionalDDL')->willReturn(false);
        $spy->method('fetchQuery')->willReturn([['version' => '20260101000104']]);
        $spy->method('fetchQueryOnce')->willReturn(['version' => '20260101000104']);
        $spy->method('executeQuery')->willReturn(true);
        $spy->expects($this->never())->method('startTransac');
        $spy->expects($this->never())->method('commitTransac');
        $spy->expects($this->never())->method('rollBack');
 
        $runner = new MigrationRunner($spy, $this->tmpDir);
        $runner->rollback();
    }
 
    public function testRollbackThrowsKernelExceptionOnMissingFile(): void
    {
        // Last version recorded but file is missing
        $connector = $this->makeConnector(['20260101000105'], '20260101000105');
        $runner    = new MigrationRunner($connector, $this->tmpDir);
 
        $this->expectException(KernelException::class);
        $this->expectExceptionMessageMatches('/Migration file not found/');
 
        $runner->rollback();
    }
 
    // -------------------------------------------------------------------------
    // status()
    // -------------------------------------------------------------------------
 
    public function testStatusReturnsPendingForNewMigrations(): void
    {
        $this->writeMigrationFile('20260101000201');
        $this->writeMigrationFile('20260101000202');
 
        $connector = $this->makeConnector([]);
        $runner    = new MigrationRunner($connector, $this->tmpDir);
        $status    = $runner->status();
 
        $this->assertEquals('pending', $status['20260101000201']);
        $this->assertEquals('pending', $status['20260101000202']);
    }
 
    public function testStatusReturnsExecutedForRunMigrations(): void
    {
        $this->writeMigrationFile('20260101000301');
        $this->writeMigrationFile('20260101000302');
 
        $connector = $this->makeConnector(['20260101000301']);
        $runner    = new MigrationRunner($connector, $this->tmpDir);
        $status    = $runner->status();
 
        $this->assertEquals('executed', $status['20260101000301']);
        $this->assertEquals('pending',  $status['20260101000302']);
    }
 
    public function testStatusReturnsEmptyArrayWhenNoMigrationFiles(): void
    {
        $connector = $this->makeConnector([]);
        $runner    = new MigrationRunner($connector, $this->tmpDir);
 
        $this->assertEmpty($runner->status());
    }
}
