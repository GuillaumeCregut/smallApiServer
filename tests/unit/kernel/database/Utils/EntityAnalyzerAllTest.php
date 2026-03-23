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
        $expect =[
            'properties'=>[],
            'manyToMany'=>[]
        ];
        $result = EntityAnalyzer::getAllEntitiesProperties($this->tempPath);
        $this->assertSame($expect, $result);
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
        $this->assertArrayHasKey('properties', $result);
        $this->assertArrayHasKey('zigouigouis', $result['properties']); 
        $this->assertArrayNotHasKey('zigouigoui_entities', $result['properties']);
        $this->assertIsArray($result['manyToMany']);
        $this->assertCount(0, $result['manyToMany']);
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
        $this->assertArrayHasKey('products', $result['properties']);
    }

    public function testClassWithManyToManyHasManyToMany(): void
    {
        file_put_contents($this->tempPath . 'Course.php', '<?php
            namespace App\Entity;
            use App\Kernel\Connector\Interfaces\EntityInterface;
            use App\Kernel\Connector\Attributes\ManyToMany;
            use App\Kernel\Connector\Datas\LazyBag;
            final class Course implements EntityInterface {
                public string $name;
                #[ManyToMany(targetEntity: EntityOwnerManyToMany::class, mappedBy: "EntitiesInversed", targetColumn: "entity1_id",  ownerColumn: "entity2_id", pivotTable: "entity1_entity2" )]
                private ?LazyBag $entities= null;

                public function getId(): int{return 0;}
                public function setId(int $id): self{return $this;}
                public static function getRepository(): string{return "";}
            }
        ');

        $result = EntityAnalyzer::getAllEntitiesProperties($this->tempPath);
        $properties = $result['properties'];
        $this->assertArrayHasKey('courses', $properties);
        $this->assertArrayHasKey('name',$properties['courses']);
        $relations = $result['manyToMany'];
        $this->assertArrayHasKey('courses', $relations);
        $this->assertArrayHasKey('entities',$relations['courses']);
        $entityRelation = $relations['courses']['entities'];
        $this->assertArrayHasKey('tableName',$entityRelation);
    }
}