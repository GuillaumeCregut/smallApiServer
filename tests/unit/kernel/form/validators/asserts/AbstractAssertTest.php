<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Form\Validator\ValidatorInterface;
use App\Kernel\Form\Validator\Assert\AbstractAssert;

class AbstractAssertTest extends TestCase
{
    public function testStringPassesWhenStringAllowed(): void
    {
        $this->assertTrue((new StringOnlyAssert())->validate('hello'));
    }

    public function testEmptyStringPassesWhenStringAllowed(): void
    {
        // type guard only checks type, not content — NotBlank handles that
        $this->assertTrue((new StringOnlyAssert())->validate(''));
    }

    public function testIntegerFailsWhenOnlyStringAllowed(): void
    {
        $this->assertFalse((new StringOnlyAssert())->validate(1));
    }

    public function testFloatFailsWhenOnlyStringAllowed(): void
    {
        $this->assertFalse((new StringOnlyAssert())->validate(1.5));
    }

    public function testBoolFailsWhenOnlyStringAllowed(): void
    {
        $this->assertFalse((new StringOnlyAssert())->validate(true));
    }

    public function testNullAlwaysPassesForStringAssert(): void
    {
        $this->assertTrue((new StringOnlyAssert())->validate(null));
    }

    public function testArrayFailsWhenOnlyStringAllowed(): void
    {
        $this->assertFalse((new StringOnlyAssert())->validate([]));
    }

    public function testObjectFailsWhenOnlyStringAllowed(): void
    {
        $this->assertFalse((new StringOnlyAssert())->validate(new \stdClass()));
    }

    public function testIntegerPassesWhenIntegerAllowed(): void
    {
        $this->assertTrue((new IntFloatAssert())->validate(42));
    }

    public function testFloatPassesWhenDoubleAllowed(): void
    {
        $this->assertTrue((new IntFloatAssert())->validate(9.99));
    }

    public function testStringFailsWhenOnlyNumericAllowed(): void
    {
        $this->assertFalse((new IntFloatAssert())->validate('42'));
    }

    public function testNullAlwaysPassesForIntFloatAssert(): void
    {
        $this->assertTrue((new IntFloatAssert())->validate(null));
    }

    public function testBoolFailsWhenOnlyNumericAllowed(): void
    {
        // gettype(true) === 'boolean', not 'integer'
        $this->assertFalse((new IntFloatAssert())->validate(true));
    }

    public function testStringPassesWhenBothStringAndIntegerAllowed(): void
    {
        $this->assertTrue((new MultiTypeAssert())->validate('hello'));
    }

    public function testIntegerPassesWhenBothStringAndIntegerAllowed(): void
    {
        $this->assertTrue((new MultiTypeAssert())->validate(42));
    }

    public function testFloatFailsWhenOnlyStringAndIntegerAllowed(): void
    {
        // float (double) is not in allowedTypes
        $this->assertFalse((new MultiTypeAssert())->validate(1.5));
    }

    public function testNullAlwaysPassesForMultiTypeAssert(): void
    {
        $this->assertTrue((new MultiTypeAssert())->validate(null));
    }

    public function testConcreteAssertImplementsValidatorInterface(): void
    {
        $this->assertInstanceOf(ValidatorInterface::class, new StringOnlyAssert());
    }

    public function testCustomErrorMessageOverridesDefault(): void
    {
        $assert = new StringOnlyAssert('my custom message for %s');
        $this->assertSame('my custom message for %s', $assert->getMessage());
    }

    public function testDefaultErrorMessageIsUsedWhenNoneProvided(): void
    {
        $assert = new StringOnlyAssert();
        $this->assertSame('field %s is invalid', $assert->getMessage());
    }
}

class StringOnlyAssert extends AbstractAssert
{
    public function __construct(?string $errorMessage = null)
    {
        $this->errorMessage = $errorMessage ?? 'field %s is invalid';
        $this->allowedTypes = ['string'];
    }
    public function check(mixed $value): bool
    {
        if (!$this->isValidType($value)) return false;
        return true;
    }
    public function getMessage(): string
    {
        return $this->errorMessage;
    }
}

class IntFloatAssert extends AbstractAssert
{
    public function __construct(?string $errorMessage = null)
    {
        $this->errorMessage = $errorMessage ?? 'field %s is invalid';
        $this->allowedTypes = ['integer', 'double'];
    }

    public function check(mixed $value): bool
    {
        if (!$this->isValidType($value)) return false;
        return true;
    }
    public function getMessage(): string
    {
        return $this->errorMessage;
    }
}

class MultiTypeAssert extends AbstractAssert
{
    public function __construct(?string $errorMessage = null)
    {
        $this->errorMessage = $errorMessage ?? 'field %s is invalid';
        $this->allowedTypes = ['string', 'integer'];
    }
    public function check(mixed $value): bool
    {
        if (!$this->isValidType($value)) return false;
        return true;
    }
    public function getMessage(): string
    {
        return $this->errorMessage;
    }
}
