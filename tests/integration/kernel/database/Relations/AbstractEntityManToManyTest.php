<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Datas\LazyBag;
use App\Kernel\Connector\AbstractEntity;
use App\Kernel\Connector\Attributes\ManyToMany;

class AbstractEntityManToManyTest extends TestCase
{
    public function testAddMarksBagDirty(): void
    {
        $course = new AECourseStub();
        $school = new AESchoolStub();
 
        $course->addSchool($school);
 
        $this->assertTrue($course->getSchools()->isDirty());
    }

    public function testAddDoesNotInitializeBag(): void
    {
        $course = new AECourseStub();
        $school = new AESchoolStub();
 
        $course->addSchool($school);
 
        $this->assertFalse($course->getSchools()->isInitialized());
    }

    public function testAddElementPresentAfterInitialization(): void
    {
        $course = new AECourseStub();
        $school = new AESchoolStub();
 
        $course->addSchool($school);
 
        $this->assertContains($school, $course->getSchools()->toArray());
    }

    public function testAddSynchronizesInverseSide(): void
    {
        $course = new AECourseStub();
        $school = new AESchoolStub();
 
        $course->addSchool($school);
 
        $this->assertContains($course, $school->getCourses()->toArray());
    }

    public function testAddMarksInverseBagDirty(): void
    {
        $course = new AECourseStub();
        $school = new AESchoolStub();
 
        $course->addSchool($school);
 
        $this->assertTrue($school->getCourses()->isDirty());
    }

    public function testAddDoesNotInitializeInverseBag(): void
    {
        $course = new AECourseStub();
        $school = new AESchoolStub();
 
        $course->addSchool($school);
 
        $this->assertFalse($school->getCourses()->isInitialized());
    }

    public function testAddMultipleElements(): void
    {
        $course  = new AECourseStub();
        $school1 = new AESchoolStub();
        $school2 = new AESchoolStub();
 
        $course->addSchool($school1);
        $course->addSchool($school2);
 
        $result = $course->getSchools()->toArray();
        $this->assertCount(2, $result);
        $this->assertContains($school1, $result);
        $this->assertContains($school2, $result);
    }

    public function testAddDoesNotAddDuplicates(): void
    {
        $course = new AECourseStub();
        $school = new AESchoolStub();
 
        $course->addSchool($school);
        $course->addSchool($school);
 
        $this->assertCount(1, $course->getSchools()->toArray());
    }

    public function testAddWithoutInversedByDoesNotSyncInverse(): void
    {
        $owner  = new AEOwnerNoInverseStub();
        $target = new AETargetNoInverseStub();
 
        // Ne doit pas lever d'exception
        $owner->addTarget($target);
 
        $this->assertTrue($owner->getTargets()->isDirty());
    }

    public function testAddWithUnknownPropertyDoesNothing(): void
    {
        $course = new AECourseStub();
        $school = new AESchoolStub();
 
        $course->addSchoolToUnknownProperty($school);
 
        $this->assertFalse($course->getSchools()->isDirty());
    }

    public function testAddWithPropertyWithoutManyToManyAttributeDoesNothing(): void
    {
        $course = new AECourseStub();
        $school = new AESchoolStub();
 
        $course->addSchoolToUnattributedProperty($school);
 
        $this->assertFalse($course->getSchools()->isDirty());
    }

    public function testRemoveMarksBagDirty(): void
    {
        $course = new AECourseStub();
        $school = new AESchoolStub();
 
        $course->removeSchool($school);
 
        $this->assertTrue($course->getSchools()->isDirty());
    }

    public function testRemoveDoesNotInitializeBag(): void
    {
        $course = new AECourseStub();
        $school = new AESchoolStub();
 
        $course->removeSchool($school);
 
        $this->assertFalse($course->getSchools()->isInitialized());
    }

    public function testRemoveElementAbsentAfterInitialization(): void
    {
        $course = new AECourseStub();
        $school = new AESchoolStub();
 
        $course->addSchool($school);
        $course->removeSchool($school);
 
        $this->assertNotContains($school, $course->getSchools()->toArray());
    }

    public function testRemoveSynchronizesInverseSide(): void
    {
        $course = new AECourseStub();
        $school = new AESchoolStub();
 
        $course->addSchool($school);
        $course->removeSchool($school);
 
        $this->assertNotContains($course, $school->getCourses()->toArray());
    }

    public function testRemoveMarksInverseBagDirty(): void
    {
        $course = new AECourseStub();
        $school = new AESchoolStub();
 
        $course->removeSchool($school);
 
        $this->assertTrue($school->getCourses()->isDirty());
    }

    public function testRemoveDoesNotInitializeInverseBag(): void
    {
        $course = new AECourseStub();
        $school = new AESchoolStub();
 
        $course->removeSchool($school);
 
        $this->assertFalse($school->getCourses()->isInitialized());
    }

    public function testRemoveNonExistentElementLeavesEmptyBag(): void
    {
        $course = new AECourseStub();
        $school = new AESchoolStub();
 
        $course->removeSchool($school);
 
        $this->assertCount(0, $course->getSchools()->toArray());
    }

    public function testAddThenRemoveLeavesBothSidesEmpty(): void
    {
        $course = new AECourseStub();
        $school = new AESchoolStub();
 
        $course->addSchool($school);
        $course->removeSchool($school);
 
        $this->assertCount(0, $course->getSchools()->toArray());
        $this->assertCount(0, $school->getCourses()->toArray());
    }

    public function testRemoveOnlyTargetedElement(): void
    {
        $course  = new AECourseStub();
        $school1 = new AESchoolStub();
        $school2 = new AESchoolStub();
 
        $course->addSchool($school1);
        $course->addSchool($school2);
        $course->removeSchool($school1);
 
        $result = $course->getSchools()->toArray();
        $this->assertCount(1, $result);
        $this->assertNotContains($school1, $result);
        $this->assertContains($school2, $result);
    }
}

class AESchoolStub extends AbstractEntity
{
    protected static ?string $repo = null;
 
    #[ManyToMany(
        targetEntity: AECourseStub::class,
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
 
    public function getCourses(): LazyBag { return $this->courses; }
    public function setCourses(LazyBag $courses): self { $this->courses = $courses; return $this; }
}
 
class AECourseStub extends AbstractEntity
{
    protected static ?string $repo = null;
 
    #[ManyToMany(
        targetEntity: AESchoolStub::class,
        ownerColumn: 'course_id',
        targetColumn: 'school_id',
        inversedBy: 'courses',
        pivotTable: 'courses_schools'
    )]
    protected LazyBag $schools;
 
    // Propriété existante sans attribut ManyToMany — pour tester le guard
    protected LazyBag $unattributed;
 
    public function __construct()
    {
        parent::__construct();
        $this->schools      = new LazyBag(fn() => []);
        $this->unattributed = new LazyBag(fn() => []);
    }
 
    public function getSchools(): LazyBag { return $this->schools; }
    public function setSchools(LazyBag $schools): self { $this->schools = $schools; return $this; }
 
    public function addSchool(AESchoolStub $school): self
    {
        $this->addToManyToMany('schools', $school);
        return $this;
    }
 
    public function removeSchool(AESchoolStub $school): self
    {
        $this->removeFromManyToMany('schools', $school);
        return $this;
    }
 
    public function addSchoolToUnknownProperty(AESchoolStub $school): self
    {
        $this->addToManyToMany('nonExistentProperty', $school);
        return $this;
    }
 
    public function addSchoolToUnattributedProperty(AESchoolStub $school): self
    {
        $this->addToManyToMany('unattributed', $school);
        return $this;
    }
}
 
class AETargetNoInverseStub extends AbstractEntity
{
    protected static ?string $repo = null;
}
 
class AEOwnerNoInverseStub extends AbstractEntity
{
    protected static ?string $repo = null;
 
    #[ManyToMany(
        targetEntity: AETargetNoInverseStub::class,
        ownerColumn: 'owner_id',
        targetColumn: 'target_id',
    )]
    protected LazyBag $targets;
 
    public function __construct()
    {
        parent::__construct();
        $this->targets = new LazyBag(fn() => []);
    }
 
    public function getTargets(): LazyBag { return $this->targets; }
    public function setTargets(LazyBag $targets): self { $this->targets = $targets; return $this; }
 
    public function addTarget(AETargetNoInverseStub $target): self
    {
        $this->addToManyToMany('targets', $target);
        return $this;
    }
}