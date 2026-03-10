<?php

use App\Kernel\Form\Validator\Assert\MoreThan;
use PHPUnit\Framework\TestCase;

class MoreThanTest extends TestCase
{
    public function testReturnsTrueWhenNullIsPassed(): void
    {
        $assert = new MoreThan(-1);
        $this->assertTrue($assert->validate(null));
    }

    public function testReturnFalseWhenNotNumberPassed(): void
    {
        $assert = new MoreThan(-1);
        $this->assertFalse($assert->validate('hello'));
        $this->assertFalse($assert->validate(true));
        $this->assertFalse($assert->validate([]));
        $obj = new stdClass();
        $this->assertFalse($assert->validate($obj));
    }

    public function testReturnFalseOnIntegerLesserThan(): void
    {
        $assert = new MoreThan(10);
        $this->assertFalse($assert->validate(5));
    }

    public function testReturnFalseOnFloatLowerThan(): void
    {
        $assert = new MoreThan(10.2);
        $this->assertFalse($assert->validate(9.8));
    }

    public function testReturnFalseOnFloatEqual(): void
    {
        $assert = new MoreThan(10.5);
        $this->assertFalse($assert->validate(10.5));
    }

    public function testReturnFalseOnIntegerEqual(): void
    {
        $assert = new MoreThan(10);
        $this->assertFalse($assert->validate(10));
    }

    public function testReturnTrueOnIntegerMoreThan(): void
    {
        $assert = new MoreThan(10);
        $this->assertTrue($assert->validate(12));
    }


    public function testReturnTrueOnFloatGreaterThan(): void
    {
        $assert = new MoreThan(10.5);
        $this->assertTrue($assert->validate(19.2));
    }

    public function testReturnsTrueWhenNegativeIntegerMoreThanNegativeExpected(): void
    {
        $assert = new MoreThan(-10);
        $this->assertTrue($assert->validate(-5));
    }

    public function testReturnsFalseWhenNegativeIntegerLowerThanNegativeExpected(): void
    {
        $assert = new MoreThan(-5);
        $this->assertFalse($assert->validate(-7));
    }

    public function testReturnsTrueWhenIntegerValueGrreaterThanFloatExpected(): void
    {
        $assert = new MoreThan(9.5);
        $this->assertTrue($assert->validate(10));
    }

    public function testReturnsTrueWhenFloatValueGreaterThanIntegerExpected(): void
    {
        $assert = new MoreThan(10);
        $this->assertTrue($assert->validate(19.5));
    }

    public function testReturnsTrueWhenNegativeValueGreaterThanZero(): void
    {
        $assert = new MoreThan(0);
        $this->assertTrue($assert->validate(1));
    }

    public function testReturnsFalseWhenZeroIsEqualToExpected(): void
    {
        $assert = new MoreThan(0);
        $this->assertFalse($assert->validate(0));
    }

    public function testDefaultErrorMessageContainsExpectedValue(): void
    {
        $assert = new MoreThan(-1);
        $this->assertSame('property %s must be more than -1', $assert->getMessage());
    }

    public function testCustomErrorMessageOverridesDefault(): void
    {
        $assert = new MoreThan(-1, 'field %s does not match');
        $this->assertSame('field %s does not match', $assert->getMessage());
    }
}