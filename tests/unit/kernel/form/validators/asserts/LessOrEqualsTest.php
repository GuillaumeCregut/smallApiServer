<?php

use App\Kernel\Form\Validator\Assert\LessOrEquals;
use PHPUnit\Framework\TestCase;

class LessOrEqualsTest extends TestCase
{
    public function testReturnsTrueWhenNullIsPassed(): void
    {
        $assert = new LessOrEquals(-1);
        $this->assertTrue($assert->validate(null));
    }

    public function testReturnFalseWhenNotNumberPassed(): void
    {
        $assert = new LessOrEquals(-1);
        $this->assertFalse($assert->validate('hello'));
        $this->assertFalse($assert->validate(true));
        $this->assertFalse($assert->validate([]));
        $obj = new stdClass();
        $this->assertFalse($assert->validate($obj));
    }

    public function testReturnFalseOnIntegerGreaterThan(): void
    {
        $assert = new LessOrEquals(10);
        $this->assertFalse($assert->validate(12));
    }

    public function testReturnFalseOnFloatGreaterThan(): void
    {
        $assert = new LessOrEquals(10.2);
        $this->assertFalse($assert->validate(12.8));
    }

    public function testReturnTrueOnIntegerLessOrEquals(): void
    {
        $assert = new LessOrEquals(10);
        $this->assertTrue($assert->validate(9));
    }


    public function testReturnTrueOnFloatLessOrEquals(): void
    {
        $assert = new LessOrEquals(10.5);
        $this->assertTrue($assert->validate(9.2));
    }

    public function testReturnsTrueWhenNegativeIntegerLessOrEqualsNegativeExpected(): void
    {
        $assert = new LessOrEquals(-1);
        $this->assertTrue($assert->validate(-5));
    }

    public function testReturnsFalseWhenNegativeIntegerGreaterThanNegativeExpected(): void
    {
        $assert = new LessOrEquals(-5);
        $this->assertFalse($assert->validate(-1));
    }

    public function testReturnsTrueWhenIntegerValueLessOrEqualsFloatExpected(): void
    {
        $assert = new LessOrEquals(9.5);
        $this->assertTrue($assert->validate(9));
    }

    public function testReturnsTrueWhenFloatValueLessThanIntegerExpected(): void
    {
        $assert = new LessOrEquals(10);
        $this->assertTrue($assert->validate(9.5));
    }

    public function testReturnsTrueWhenNegativeValueLessThanZero(): void
    {
        $assert = new LessOrEquals(0);
        $this->assertTrue($assert->validate(-1));
    }

    public function testReturnsTrueWhenZeroIsEqualToExpected(): void
    {
        $assert = new LessOrEquals(0);
        $this->assertTrue($assert->validate(0));
    }

    public function testReturnsTrueWhenIntIsEqualToExpected(): void
    {
        $assert = new LessOrEquals(10);
        $this->assertTrue($assert->validate(10));
    }

    public function testReturnsTrueWhenIntIsEqualToFloatExpected(): void
    {
        $assert = new LessOrEquals(10.0);
        $this->assertTrue($assert->validate(10));
    }

    public function testReturnsTrueWhenFloatIsEqualToExpected(): void
    {
        $assert = new LessOrEquals(10.0);
        $this->assertTrue($assert->validate(10.0));
    }

    public function testReturnsTrueWhenFloatIsEqualToIntExpected(): void
    {
        $assert = new LessOrEquals(10);
        $this->assertTrue($assert->validate(10.0));
    }

    public function testDefaultErrorMessageContainsExpectedValue(): void
    {
        $assert = new LessOrEquals(-1);
        $this->assertSame('property %s must be less or equals than -1', $assert->getMessage());
    }

    public function testCustomErrorMessageOverridesDefault(): void
    {
        $assert = new LessOrEquals(-1, 'field %s does not match');
        $this->assertSame('field %s does not match', $assert->getMessage());
    }
}