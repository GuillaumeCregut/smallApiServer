<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Datas\LazyBag;
use App\Kernel\Connector\AbstractEntity;
use App\Kernel\Connector\AbstractRepository;
use App\Kernel\Connector\ConnectorDispatcher;
use App\Kernel\Connector\Attributes\ManyToMany;
use App\Kernel\Connector\Attributes\NotStored;
use App\Kernel\Connector\Interfaces\ConnectorInterface;

class AbstractRepoManyToManyTest extends TestCase
{
    protected function setUp(): void
    {
        ConnectorDispatcher::resetConnector();
        $connector = $this->createStub(ConnectorInterface::class);
        ConnectorDispatcher::setConnector($connector);
    }

    public function testCreateSqlTableDoesNotAddManyToManyColumn(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        ConnectorDispatcher::setConnector($connector);
        $repository = new RepoM2MCourseStub();
 
        $sql = $repository->createSqlTable();
 
        $this->assertEquals(
            'CREATE TABLE m2mcourse_stubs (id INT NOT NULL AUTO_INCREMENT , name VARCHAR(255) NOT NULL, PRIMARY KEY (id))',
            $sql
        );
        $this->assertStringNotContainsString('school', $sql);
    }

    public function testFindSetsManyToManyBag(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')
            ->willReturn([['id' => 1, 'name' => 'Math']]);
        ConnectorDispatcher::setConnector($connector);
        $repository = new RepoM2MCourseStub();
 
        /** @var M2MCourseStub $result */
        $result = $repository->find(1);
 
        $this->assertInstanceOf(LazyBag::class, $result->getSchools());
    }

    public function testFindManyToManyBagIsNotInitializedOnFind(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')
            ->willReturn([['id' => 1, 'name' => 'Math']]);
        ConnectorDispatcher::setConnector($connector);
        $repository = new RepoM2MCourseStub();
 
        /** @var M2MCourseStub $result */
        $result = $repository->find(1);
 
        $this->assertFalse($result->getSchools()->isInitialized());
    }

    public function testFindManyToManyBagLoadsFromPivotTableOnAccess(): void
    {
        $fetchCalls = [];
        $connector  = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')
            ->willReturnCallback(function (string $sql, array $params) use (&$fetchCalls) {
                $fetchCalls[] = $sql;
                
                if (str_contains($sql, 'WHERE id =')) {
                    return [['id' => 1, 'name' => 'Math']];
                }
               
                if (str_contains($sql, 'courses_schools')) {
                    return [
                        ['course_id' => 1, 'school_id' => 10],
                        ['course_id' => 1, 'school_id' => 20],
                    ];
                }
                
                if (str_contains($sql, 'm2_m_school_stubs')) {
                    return [['id' => (int) $params[':id'] ?? $params['id'], 'name' => 'School']];
                }
                return [];
            });
        ConnectorDispatcher::setConnector($connector);
        $repository = new RepoM2MCourseStub();
 
        /** @var M2MCourseStub $result */
        $result  = $repository->find(1);
        $schools = $result->getSchools()->toArray(); 
 
        $pivotCalls = array_filter($fetchCalls, fn($sql) => str_contains($sql, 'courses_schools'));
        $this->assertNotEmpty($pivotCalls);
    }

    public function testManyToManyPropertyIsNotStored(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        ConnectorDispatcher::setConnector($connector);
        $repository = new RepoM2MCourseStub();
 
        $sql = $repository->createSqlTable();
 
        $this->assertStringNotContainsString('school_id', $sql);
        $this->assertStringNotContainsString('schools', $sql);
    }

    public function testSaveInsertIgnoresManyToManyBag(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('executeQuery')->willReturn(5);
        ConnectorDispatcher::setConnector($connector);
        $repository = new RepoM2MCourseStub();
 
        $entity = new M2MCourseStub();
        $entity->setName('Physics');
        $result = $repository->save($entity);
 
        $this->assertEquals(
            'INSERT INTO m2mcourse_stubs (name) VALUES (?)',
            $repository->sql
        );
        $this->assertEquals(5, $result->getId());
    }

     public function testSaveUpdateIgnoresManyToManyBag(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('executeQuery')->willReturn(true);
        ConnectorDispatcher::setConnector($connector);
        $repository = new RepoM2MCourseStub();
 
        $entity = new M2MCourseStub();
        $entity->setId(3);
        $entity->setName('Physics');
        $result = $repository->save($entity);
 
        $this->assertEquals(
            'UPDATE m2mcourse_stubs SET name = ? WHERE id = ?',
            $repository->sql
        );
    }

     public function testFindSetsInverseSideBag(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')
            ->willReturn([['id' => 1, 'name' => 'Harvard']]);
        ConnectorDispatcher::setConnector($connector);
        $repository = new RepoM2MSchoolStub();
 
        /** @var M2MSchoolStub $result */
        $result = $repository->find(1);
 
        $this->assertInstanceOf(LazyBag::class, $result->getCourses());
    }

    public function testFindInverseSideBagIsNotInitializedOnFind(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')
            ->willReturn([['id' => 1, 'name' => 'Harvard']]);
        ConnectorDispatcher::setConnector($connector);
        $repository = new RepoM2MSchoolStub();
 
        /** @var M2MSchoolStub $result */
        $result = $repository->find(1);
 
        $this->assertFalse($result->getCourses()->isInitialized());
    }
}

class M2MSchoolStub extends AbstractEntity
{
    #[NotStored]
    protected static ?string $repo = RepoM2MSchoolStub::class;
 
    protected string $name = '';
 
    #[ManyToMany(
        targetEntity: M2MCourseStub::class,
        ownerColumn: 'school_id',
        targetColumn: 'course_id',
        mappedBy: 'schools',
        pivotTable: 'courses_schools'
    )]
    protected LazyBag $courses;
 
    public function __construct()
    {
        parent::__construct();
        $this->courses = new LazyBag(fn() => []);
    }
 
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getCourses(): LazyBag { return $this->courses; }
    public function setCourses(LazyBag $courses): self { $this->courses = $courses; return $this; }
}
 
class M2MCourseStub extends AbstractEntity
{
    #[NotStored]
    protected static ?string $repo = RepoM2MCourseStub::class;
 
    protected string $name = '';
 
    #[ManyToMany(
        targetEntity: M2MSchoolStub::class,
        ownerColumn: 'course_id',
        targetColumn: 'school_id',
        inversedBy: 'courses',
        pivotTable: 'courses_schools'
    )]
    protected LazyBag $schools;
 
    public function __construct()
    {
        parent::__construct();
        $this->schools = new LazyBag(fn() => []);
    }
 
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getSchools(): LazyBag { return $this->schools; }
    public function setSchools(LazyBag $schools): self { $this->schools = $schools; return $this; }
}
 
// ---------------------------------------------------------------------------
// Repositories stubs
// ---------------------------------------------------------------------------
 
class RepoM2MSchoolStub extends AbstractRepository
{
    protected ?string $entity = M2MSchoolStub::class;
}
 
class RepoM2MCourseStub extends AbstractRepository
{
    protected ?string $entity = M2MCourseStub::class;
}
 