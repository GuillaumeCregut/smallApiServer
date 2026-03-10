<?php

use App\Kernel\Connector\Interfaces\EntityInterface;
use App\Kernel\Form\Validator\Assert\Optional;
use App\Kernel\Form\Validator\DataValidator;
use App\Kernel\Form\Validator\ValidatorInterface;
use PHPUnit\Framework\TestCase;

class DataValidatorTest extends TestCase
{

    public function testValidateNoEntity(): void
    {

        $this->expectException(InvalidArgumentException::class);
        DataValidator::validate(stdClass::class, []);
    }

    public function testThrowsWhenClassDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DataValidator::validate('NonExistentClass', []);
    }


    public function testValidateNoElement(): void
    {
        $datas = [];
        $result = DataValidator::validate(EntityToValidate1::class, $datas);
        $this->assertTrue($result);
    }

    public function testValidateNotBlankElementEmpty(): void
    {
        $datas = [];
        $result = DataValidator::validate(EntityToValidate2::class, $datas);
        $this->assertFalse($result);
    }

    public function testValidateNoKeyInData(): void
    {
        $datas = [12];
        $result = DataValidator::validate(EntityToValidate2::class, $datas);
        $this->assertFalse($result);
    }

    public function testValidateBlankInData(): void
    {
        $datas = ['id' => ''];
        $result = DataValidator::validate(EntityToValidate2::class, $datas);
        $this->assertTrue($result);
    }

    public function testUnValidateBlankInData(): void
    {
        $datas = ['id' => ''];
        $result = DataValidator::validate(EntityToValidate3::class, $datas);
        $this->assertFalse($result);
    }

    public function testValidateKeyInData(): void
    {
        $datas = ['id' => 12];
        $result = DataValidator::validate(EntityToValidate2::class, $datas);
        $this->assertTrue($result);
    }

    public function testValidateMissingOneOfMultipleKeys(): void
    {
        // has 'id' but missing 'name' → should fail
        $datas = ['id' => 12];
        $result = DataValidator::validate(EntityToValidate4::class, $datas);
        $this->assertFalse($result);
    }

    public function testValidateAllKeysPresent(): void
    {
        $datas = ['id' => 12, 'name' => 'John'];
        $result = DataValidator::validate(EntityToValidate4::class, $datas);
        $this->assertTrue($result);
    }

    public function testValidateOneOfTwoAttributesFails(): void
    {
        $datas = ['id' => 12];
        $result = DataValidator::validate(EntityToValidate5::class, $datas);
        $this->assertFalse($result);
    }

    public function testValidateExtraKeysInDataAreIgnored(): void
    {
        $datas = ['id' => 12, 'unexpected' => 'ghost'];
        $result = DataValidator::validate(EntityToValidate2::class, $datas);
        $this->assertTrue($result);
    }

    public function testIsNullAllowed(): void
    {
        $datas = ['id' => 12, 'unexpected' => 'ghost'];
        $result = DataValidator::validate(EntityToValidate2::class, $datas);
        $this->assertTrue($result);
    }

    public function testObjectPropertyWithoutAttributeIsIgnored(): void
    {
        // 'relation' has no attribute → passes regardless
        $result = DataValidator::validate(EntityWithRelation3::class, ['name' => 'John']);
        $this->assertTrue($result);
    }

    public function testObjectPropertyWithoutAttributeIsIgnoredEvenIfPresent(): void
    {
        $result = DataValidator::validate(EntityWithRelation3::class, [
            'name'     => 'John',
            'relation' => new RelatedEntity(),
        ]);
        $this->assertTrue($result);
    }

    public function testReturnsTrueWhenFileFieldIsPassedViaFilesArray(): void
    {
        // 'avatar' comes from $files, not $values
        $result = DataValidator::validate(
            EntityWithFile::class,
            ['name'   => 'John'],
            ['avatar' => ['name' => 'photo.jpg', 'size' => 1024]]
        );
        $this->assertTrue($result);
    }

    public function testReturnsFalseWhenFileFieldIsMissing(): void
    {
        // 'avatar' is absent from both $values and $files
        $result = DataValidator::validate(
            EntityWithFile::class,
            ['name' => 'John'],
            []
        );
        $this->assertFalse($result);
    }

    public function testFileFieldCanAlsoBePassedInValues(): void
    {
        // files merged into values, so passing avatar in $values works too
        $result = DataValidator::validate(
            EntityWithFile::class,
            ['name' => 'John', 'avatar' => ['name' => 'photo.jpg']],
            []
        );
        $this->assertTrue($result);
    }

    public function testReturnsTrueWhenOptionalFieldIsAbsent(): void
    {
        // 'optional' key is missing but marked #[Optional] → should pass
        $result = DataValidator::validate(OptionalFieldEntity::class, ['name' => 'John']);
        $this->assertTrue($result);
    }

    public function testReturnsTrueWhenOptionalFieldIsPresent(): void
    {
        $result = DataValidator::validate(OptionalFieldEntity::class, [
            'name'     => 'John',
            'optional' => 'some value',
        ]);
        $this->assertTrue($result);
    }

    public function testGetErrorsIsEmptyOnSuccess(): void
    {
        DataValidator::validate(ValidEntity::class, ['name' => 'John']);
        $this->assertEmpty(DataValidator::getErrors());
    }

    public function testErrorsAreResetBetweenValidateCalls(): void
    {
        // first call pollutes errors
        DataValidator::validate(InvalidEntity::class, ['name' => 'John']);
        $this->assertNotEmpty(DataValidator::getErrors());

        // second call must reset them
        DataValidator::validate(ValidEntity::class, ['name' => 'John']);
        $this->assertEmpty(DataValidator::getErrors());
    }

    public function testGetErrorsUsesPropertyNameAsKey(): void
    {
        DataValidator::validate(InvalidEntity::class, ['name' => 'John']);
        $this->assertArrayHasKey('name', DataValidator::getErrors());
    }

    public function testGetErrorsUsesCorrectPropertyNameAsKey(): void
    {
        // 'age' fails, not 'name' — key must be 'age', not hardcoded 'name'
        DataValidator::validate(MultiOneInvalidEntity::class, [
            'name' => 'John',
            'age'  => 30,
        ]);
        $errors = DataValidator::getErrors();
        $this->assertArrayHasKey('age', $errors);
        $this->assertArrayNotHasKey('name', $errors);
    }

    public function testGetErrorsContainsMissingValueMessageWhenKeyAbsent(): void
    {
        DataValidator::validate(ValidEntity::class, []);
        $errors = DataValidator::getErrors();
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('error', $errors['name']);
    }

    public function testGetErrorsContainsAssertClassNameAsKey(): void
    {
        DataValidator::validate(InvalidEntity::class, ['name' => 'John']);
        $errors = DataValidator::getErrors();
        $this->assertArrayHasKey(AlwaysInvalid::class, $errors['name']);
    }

    public function testGetErrorsContainsFormattedMessage(): void
    {
        DataValidator::validate(InvalidEntity::class, ['name' => 'John']);
        $errors = DataValidator::getErrors();
        // getMessage() returns 'Field %s is always invalid', sprintf fills %s with property name
        $this->assertSame('Field name is always invalid', $errors['name'][AlwaysInvalid::class]);
    }

    public function testOptionalMixWillReturnTrueOnNoValues(): void
    {
        $result = DataValidator::validate(OptionalAndAllwaysInvalid::class, [
            
        ]);
        $this->assertTrue($result);
    }

    public function testOptionalMixWillReturnFalseOnValues(): void
    {
        $result = DataValidator::validate(OptionalAndAllwaysInvalid::class, [
            'name'=>'John'
        ]);
        $this->assertFalse($result);
    }

    public function testOptionalMixWillReturnTrueOnValues(): void
    {
        $result = DataValidator::validate(OptionalAndAllwaysValid::class, [
            'name'=>'John'
        ]);
        $this->assertTrue($result);
    }

    public function testOptionalMixWillReturnTrueOnNoValuesWithValid(): void
    {
        $result = DataValidator::validate(OptionalAndAllwaysValid::class, [
        ]);
        $this->assertTrue($result);
    }
}
class OptionalAndAllwaysValid implements EntityInterface
{
    private int $id;

    #[Optional]
    #[AlwaysValid]
    private ?string $name;

    public static function getRepository(): string
    {
        return '';
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
}

class OptionalAndAllwaysInvalid implements EntityInterface
{
    private int $id;

    #[Optional]
    #[AlwaysInvalid]
    private ?string $name;

    public static function getRepository(): string
    {
        return '';
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
}

class OptionalFieldEntity implements EntityInterface
{
    private int $id;
    #[AlwaysValid]
    private string $name;
    #[Optional]
    private ?string $optional;
    public static function getRepository(): string
    {
        return '';
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
}

class MultiOneInvalidEntity implements EntityInterface
{
    private int $id;
    #[AlwaysValid]
    private string $name;
    #[AlwaysInvalid]
    private int $age;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public static function getRepository(): string
    {
        return '';
    }
}

class EntityToValidate1 implements EntityInterface
{
    private int $id;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public static function getRepository(): string
    {
        return '';
    }
}

class EntityToValidate2 implements EntityInterface
{
    #[AlwaysValid]
    private int $id;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public static function getRepository(): string
    {
        return '';
    }
}

class EntityToValidate3 implements EntityInterface
{
    #[AlwaysInvalid]
    private int $id;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public static function getRepository(): string
    {
        return '';
    }
}

class EntityToValidate4 implements EntityInterface
{
    #[AlwaysValid]
    private int $id;

    #[AlwaysValid]
    private string $name;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public static function getRepository(): string
    {
        return '';
    }
}

class EntityToValidate5 implements EntityInterface
{
    #[AlwaysValid]
    #[AlwaysInvalid]
    private int $id;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public static function getRepository(): string
    {
        return '';
    }
}

class EntityToValidate6 implements EntityInterface
{
    #[AlwaysValid]
    private int $id;

    #[Optional]
    private int $age;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public static function getRepository(): string
    {
        return '';
    }
}

class RelatedEntity implements EntityInterface
{
    private int $id;
    public static function getRepository(): string
    {
        return '';
    }
    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
}

class ValidEntity implements EntityInterface
{
    private int $id;
    #[AlwaysValid]
    private string $name;
    public static function getRepository(): string
    {
        return '';
    }
    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
}

class InvalidEntity implements EntityInterface
{
    private int $id;
    #[AlwaysInvalid]
    private string $name;
    public static function getRepository(): string
    {
        return '';
    }
    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
}

class EntityWithRelation3 implements EntityInterface
{
    private int $id;
    private RelatedEntity $relation;
    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public static function getRepository(): string
    {
        return '';
    }
}


class EntityWithFile implements EntityInterface
{
    private int $id;
    #[AlwaysValid]
    private string $name;
    #[AlwaysValid]
    private mixed $avatar; // will come from $files
    public static function getRepository(): string
    {
        return '';
    }

    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
}


#[Attribute]
class AlwaysValid implements ValidatorInterface
{
    public function validate(mixed $value): bool
    {
        return true;
    }

    public function getMessage(): string
    {
        return "Allways Valid";
    }
}

#[Attribute]
class AlwaysInvalid implements ValidatorInterface
{
    public function validate(mixed $value): bool
    {
        return false;
    }

    public function getMessage(): string
    {
        return "Field %s is always invalid";
    }
}
