<?php

use App\Kernel\Connector\Interfaces\EntityInterface;
use App\Kernel\Validator\Assert\NotBlank;
use App\Kernel\Validator\DataValidator;
use App\Kernel\Validator\ValidatorInterface;
use PHPUnit\Framework\TestCase;

class DataValidatorTest extends TestCase
{

    public function testValidateNoEntity(): void
    {

        $this->expectException(InvalidArgumentException::class);
        DataValidator::validate(stdClass::class, []);
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



#[Attribute]
class AlwaysValid implements ValidatorInterface
{
    public function validate(mixed $value): bool
    {
        return true;
    }
}

#[Attribute]
class AlwaysInvalid implements ValidatorInterface
{
    public function validate(mixed $value): bool
    {
        return false;
    }
}
