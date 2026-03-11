<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Files\FileUpload;
use App\Kernel\Form\FormHandler;
use App\Kernel\Form\Validator\Assert\Min;
use App\Kernel\Form\Validator\Assert\File;
use App\Kernel\Form\Validator\Assert\NotBlank;
use App\Kernel\Form\Validator\Assert\Optional;
use App\Kernel\Connector\Interfaces\EntityInterface;

class FormHandlerTest extends TestCase
{
    public function testReturnsArrayWithExpectedKeys(): void
    {
        $result = FormHandler::handle(HandlerEmptyEntity::class, []);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('values', $result);
        $this->assertArrayHasKey('errors', $result);
    }

    public function testValidKeyIsBoolean(): void
    {
        $result = FormHandler::handle(HandlerEmptyEntity::class, []);
        $this->assertIsBool($result['valid']);
    }

    public function testValuesKeyIsArray(): void
    {
        $result = FormHandler::handle(HandlerEmptyEntity::class, []);
        $this->assertIsArray($result['values']);
    }

    public function testErrorsKeyIsArray(): void
    {
        $result = FormHandler::handle(HandlerEmptyEntity::class, []);
        $this->assertIsArray($result['errors']);
    }

    public function testReturnsTrueWhenDataIsValid(): void
    {
        $result = FormHandler::handle(HandlerStringEntity::class, ['name' => 'John']);
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testReturnsTrueOnEmptyEntity(): void
    {
        $result = FormHandler::handle(HandlerEmptyEntity::class, []);
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValuesAreTrimmedBeforeValidation(): void
    {
        $result = FormHandler::handle(HandlerStringEntity::class, ['name' => '  John  ']);
        $this->assertTrue($result['valid']);
        $this->assertSame('John', $result['values']['name']);
    }

    public function testHtmlTagsAreStrippedBeforeValidation(): void
    {
        $result = FormHandler::handle(HandlerStringEntity::class, ['name' => '<b>John</b>']);
        $this->assertTrue($result['valid']);
        $this->assertSame('John', $result['values']['name']);
    }

    public function testHtmlSpecialCharsAreEscapedBeforeValidation(): void
    {
        $result = FormHandler::handle(HandlerStringEntity::class, ['name' => 'John & Doe']);
        $this->assertTrue($result['valid']);
        $this->assertSame('John &amp; Doe', $result['values']['name']);
    }

    public function testSanitizationHappensBeforeValidation(): void
    {
        // '  ' trimmed to '' → NotBlank fails → pipeline catches it after sanitization
        $result = FormHandler::handle(HandlerStringEntity::class, ['name' => '  ']);
        $this->assertFalse($result['valid']);
    }

    public function testValuesAreCastedBeforeValidation(): void
    {
        // raw string '25' must be cast to int before Min(0) validates it
        $result = FormHandler::handle(HandlerCastableEntity::class, ['age' => '25']);
        $this->assertTrue($result['valid']);
        $this->assertSame(25, $result['values']['age']);
    }

    public function testCastingHappensAfterSanitization(): void
    {
        // sanitizer touches strings only — int passes through untouched
        $result = FormHandler::handle(HandlerCastableEntity::class, ['age' => 25]);
        $this->assertTrue($result['valid']);
        $this->assertSame(25, $result['values']['age']);
    }

    public function testReturnsFalseWhenDataIsInvalid(): void
    {
        $result = FormHandler::handle(HandlerStringEntity::class, ['name' => '']);
        $this->assertFalse($result['valid']);
    }

    public function testErrorsArePopulatedOnFailure(): void
    {
        $result = FormHandler::handle(HandlerStringEntity::class, ['name' => '']);
        $this->assertNotEmpty($result['errors']);
        $this->assertArrayHasKey('name', $result['errors']);
    }

    public function testErrorsAreEmptyOnSuccess(): void
    {
        $result = FormHandler::handle(HandlerStringEntity::class, ['name' => 'John']);
        $this->assertEmpty($result['errors']);
    }

    public function testReturnsFalseWhenKeyIsMissing(): void
    {
        $result = FormHandler::handle(HandlerStringEntity::class, []);
        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('name', $result['errors']);
    }

    public function testReturnsTrueWhenOptionalFieldIsAbsent(): void
    {
        $result = FormHandler::handle(HandlerOptionalEntity::class, ['name' => 'John']);
        $this->assertTrue($result['valid']);
    }

    public function testReturnsTrueWhenOptionalFieldIsPresent(): void
    {
        $result = FormHandler::handle(HandlerOptionalEntity::class, [
            'name'     => 'John',
            'nickname' => 'Johnny',
        ]);
        $this->assertTrue($result['valid']);
    }

    public function testReturnsFalseWhenOptionalFieldIsPresentButInvalid(): void
    {
        // nickname is present but blank → NotBlank fails
        $result = FormHandler::handle(HandlerOptionalEntity::class, [
            'name'     => 'John',
            'nickname' => '',
        ]);
        $this->assertFalse($result['valid']);
    }

    public function testReturnsTrueWhenAllFieldsAreValid(): void
    {
        $result = FormHandler::handle(HandlerMultipleFieldsEntity::class, [
            'name' => 'John',
            'age'  => '25',
        ]);
        $this->assertTrue($result['valid']);
    }

    public function testReturnsFalseWhenOneOfMultipleFieldsFails(): void
    {
        $result = FormHandler::handle(HandlerMultipleFieldsEntity::class, [
            'name' => '',    // blank → NotBlank fails
            'age'  => '25',
        ]);
        $this->assertFalse($result['valid']);
    }

    public function testFilesArePassedToValidator(): void
    {
        $file = $this->createStub(FileUpload::class);
        $file->method('isValid')->willReturn(true);

        $result = FormHandler::handle(
            HandlerFileEntity::class,
            ['name' => 'John'],
            ['avatar' => $file]
        );
        $this->assertTrue($result['valid']);
    }

    public function testReturnsFalseWhenFileIsMissing(): void
    {
        $result = FormHandler::handle(
            HandlerFileEntity::class,
            ['name' => 'John'],
            []
        );
        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('avatar', $result['errors']);
    }

    public function testReturnsFalseWhenFileIsInvalid(): void
    {
        $file = $this->createStub(FileUpload::class);
        $file->method('isValid')->willReturn(false);

        $result = FormHandler::handle(
            HandlerFileEntity::class,
            ['name' => 'John'],
            ['avatar' => $file]
        );
        $this->assertFalse($result['valid']);
    }

    public function testValuesInResultAreReadyToHydrate(): void
    {
        // values must be sanitized and casted — ready for hydrator
        $result = FormHandler::handle(HandlerMultipleFieldsEntity::class, [
            'name' => '  <b>John</b>  ',
            'age'  => '25',
        ]);
        $this->assertSame('John', $result['values']['name']); // sanitized
        $this->assertSame(25, $result['values']['age']);       // casted
    }
}

class HandlerEmptyEntity implements EntityInterface
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

class HandlerStringEntity implements EntityInterface
{
    #[NotBlank]
    private string $name;

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

class HandlerCastableEntity implements EntityInterface
{
    #[Min(0)]
    private int $age;

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

class HandlerOptionalEntity implements EntityInterface
{
    #[NotBlank]
    private string $name;
    #[Optional]
    #[NotBlank]
    private ?string $nickname;

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

class HandlerFileEntity implements EntityInterface
{
    #[NotBlank]
    private string $name;
    #[File(2097152)]
    private mixed $avatar;

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

class HandlerMultipleFieldsEntity implements EntityInterface
{
    #[NotBlank]
    private string $name;
    #[Min(0)]
    private int $age;

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
