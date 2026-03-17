<?php

use App\Kernel\Connector\Utils\EntityAnalyzer;
use PHPUnit\Framework\TestCase;

class EntityAnalyzerAllTest extends TestCase
{
    private string $tempPath;

    protected function setUp(): void
    {
        $this->tempPath = sys_get_temp_dir() . '/entities_test_' . uniqid() . '/';
        mkdir($this->tempPath);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tempPath . '*.php'));
        rmdir($this->tempPath);
    }

    public function testReturnsEmptyArrayWhenNoClassesFound(): void
    {
        // Empty dir, no files
        $result = EntityAnalyzer::getAllEntitiesProperties($this->tempPath);
        $this->assertSame([], $result);
    }

    public function testThrowsExceptionIfPathDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        EntityAnalyzer::getAllEntitiesProperties('/non/existent/path/');
    }

    public function testStripsEntitySuffixAndReturnsProperties(): void
    {
        // Write a real PHP entity file in the temp dir
        file_put_contents($this->tempPath . 'ZigouigouiEntity.php', '<?php
            namespace App\Entity;
            use App\Kernel\Connector\Interfaces\EntityInterface;
            final class ZigouigouiEntity implements EntityInterface{
                public string $name;
                public function getId(): int{return 0;}
                public function setId(int $id): self{return $this;}
                public static function getRepository(): string{return "";}
            }
        ');

        $result = EntityAnalyzer::getAllEntitiesProperties($this->tempPath);

        $this->assertArrayHasKey('zigouigouis', $result); // assuming propertyToColumn lowercases
        $this->assertArrayNotHasKey('zigouigoui_entities', $result);
    }

    public function testKeepsClassNameIfNoEntitySuffix(): void
    {
        file_put_contents($this->tempPath . 'Product.php', '<?php
            namespace App\Entity;
            use App\Kernel\Connector\Interfaces\EntityInterface;
            final class Product implements EntityInterface {
                public string $name;
                public function getId(): int{return 0;}
                public function setId(int $id): self{return $this;}
                public static function getRepository(): string{return "";}
            }
        ');

        $result = EntityAnalyzer::getAllEntitiesProperties($this->tempPath);
        $this->assertArrayHasKey('products', $result);
    }
}