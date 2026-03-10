<?php

use App\Kernel\Form\Validator\Assert\LessThan;
use PHPUnit\Framework\TestCase;

class LessThanTest extends TestCase
{
    public function testReturnsTrueWhenNullIsPassed(): void
    {
        $assert = new LessThan(-1);
        $this->assertTrue($assert->validate(null));
    }

    public function testReturnFalseWhenNotNumberPassed(): void
    {
        $assert = new LessThan(-1);
        $this->assertFalse($assert->validate('hello'));
        $this->assertFalse($assert->validate(true));
        $this->assertFalse($assert->validate([]));
        $obj = new stdClass();
        $this->assertFalse($assert->validate($obj));
    }

    public function testReturnFalseOnIntegerGreaterThan(): void
    {
        $assert = new LessThan(10);
        $this->assertFalse($assert->validate(12));
    }

    public function testReturnFalseOnFloatGreaterThan(): void
    {
        $assert = new LessThan(10.2);
        $this->assertFalse($assert->validate(12.8));
    }

    public function testReturnFalseOnFloatEqual(): void
    {
        $assert = new LessThan(10.5);
        $this->assertFalse($assert->validate(10.5));
    }

    public function testReturnFalseOnIntegerEqual(): void
    {
        $assert = new LessThan(10);
        $this->assertFalse($assert->validate(10));
    }

    public function testReturnTrueOnIntegerLessThan(): void
    {
        $assert = new LessThan(10);
        $this->assertTrue($assert->validate(9));
    }


    public function testReturnTrueOnFloatLessThan(): void
    {
        $assert = new LessThan(10.5);
        $this->assertTrue($assert->validate(9.2));
    }

    public function testReturnsTrueWhenNegativeIntegerLessThanNegativeExpected(): void
    {
        $assert = new LessThan(-1);
        $this->assertTrue($assert->validate(-5));
    }

    public function testReturnsFalseWhenNegativeIntegerGreaterThanNegativeExpected(): void
    {
        $assert = new LessThan(-5);
        $this->assertFalse($assert->validate(-1));
    }

    public function testReturnsTrueWhenIntegerValueLessThanFloatExpected(): void
    {
        $assert = new LessThan(9.5);
        $this->assertTrue($assert->validate(9));
    }

    public function testReturnsTrueWhenFloatValueLessThanIntegerExpected(): void
    {
        $assert = new LessThan(10);
        $this->assertTrue($assert->validate(9.5));
    }

    public function testReturnsTrueWhenNegativeValueLessThanZero(): void
    {
        $assert = new LessThan(0);
        $this->assertTrue($assert->validate(-1));
    }

    public function testReturnsFalseWhenZeroIsEqualToExpected(): void
    {
        $assert = new LessThan(0);
        $this->assertFalse($assert->validate(0));
    }

    public function testDefaultErrorMessageContainsExpectedValue(): void
    {
        $assert = new LessThan(-1);
        $this->assertSame('property %s must be less than -1', $assert->getMessage());
    }

    public function testCustomErrorMessageOverridesDefault(): void
    {
        $assert = new LessThan(-1, 'field %s does not match');
        $this->assertSame('field %s does not match', $assert->getMessage());
    }
}