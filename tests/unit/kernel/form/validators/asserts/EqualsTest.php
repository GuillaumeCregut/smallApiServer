<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Form\Validator\Assert\Equals;

class EqualsTest extends TestCase
{
     public function testReturnsTrueWhenStringMatches(): void
    {
        $this->assertTrue((new Equals('hello'))->validate('hello'));
    }

    public function testReturnsFalseWhenStringDoesNotMatch(): void
    {
        $this->assertFalse((new Equals('hello'))->validate('world'));
    }

    public function testReturnsFalseWhenStringMatchesButTypeDoesNot(): void
    {
        // strict === comparison: '1' !== 1
        $this->assertFalse((new Equals('1'))->validate(1));
    }

    public function testReturnsTrueWhenIntegerMatches(): void
    {
        $this->assertTrue((new Equals(42))->validate(42));
    }

    public function testReturnsFalseWhenIntegerDoesNotMatch(): void
    {
        $this->assertFalse((new Equals(42))->validate(43));
    }

    public function testReturnsFalseWhenIntegerMatchesButTypeDoesNot(): void
    {
        // strict === comparison: 1 !== true
        $this->assertFalse((new Equals(1))->validate(true));
    }

    public function testReturnsTrueWhenFloatMatches(): void
    {
        $this->assertTrue((new Equals(9.99))->validate(9.99));
    }

    public function testReturnsFalseWhenFloatDoesNotMatch(): void
    {
        $this->assertFalse((new Equals(9.99))->validate(9.98));
    }

    public function testReturnsFalseWhenFloatMatchesValueButTypeDoesNot(): void
    {
        // strict === comparison: 1.0 !== 1
        $this->assertFalse((new Equals(1.0))->validate(1));
    }

    public function testReturnsTrueWhenBoolTrueMatches(): void
    {
        $this->assertTrue((new Equals(true))->validate(true));
    }

    public function testReturnsTrueWhenBoolFalseMatches(): void
    {
        $this->assertTrue((new Equals(false))->validate(false));
    }

    public function testReturnsFalseWhenBoolDoesNotMatch(): void
    {
        $this->assertFalse((new Equals(true))->validate(false));
    }

    public function testReturnsFalseWhenBoolMatchesValueButTypeDoesNot(): void
    {
        // strict === comparison: true !== 1
        $this->assertFalse((new Equals(true))->validate(1));
    }

    public function testReturnsTrueWhenExpectedNullAndValueIsNull(): void
    {
        // validate() is overridden — null is handled, not passed through
        $this->assertTrue((new Equals(null))->validate(null));
    }

    public function testReturnsFalseWhenExpectedNullButValueIsNot(): void
    {
        $this->assertFalse((new Equals(null))->validate(''));
        $this->assertFalse((new Equals(null))->validate(0));
        $this->assertFalse((new Equals(null))->validate(false));
    }

    public function testReturnsTrueWhenNullValueAndNonNullExpected(): void
    {
        // validate() is overridden — null checked against expected, not passed through
        $this->assertFalse((new Equals('hello'))->validate(null));
    }

    public function testReturnsFalseOnArray(): void
    {
        $this->assertFalse((new Equals('hello'))->validate([]));
    }

    public function testReturnsFalseOnObject(): void
    {
        $obj = new \stdClass();
        $this->assertFalse((new Equals('hello'))->validate($obj));
    }

    public function testUsesStrictComparisonNotLoose(): void
    {
        // == would pass these, === must not
        $this->assertFalse((new Equals(0))->validate(''));
        $this->assertFalse((new Equals(0))->validate(false));
        $this->assertFalse((new Equals(0))->validate(null));
        $this->assertFalse((new Equals(''))->validate(false));
        $this->assertFalse((new Equals(''))->validate(null));
    }

    public function testDefaultErrorMessageContainsExpectedValue(): void
    {
        $assert = new Equals('hello');
        $this->assertSame('property %s must be equals to hello', $assert->getMessage());
    }

    public function testCustomErrorMessageOverridesDefault(): void
    {
        $assert = new Equals('hello', 'field %s does not match');
        $this->assertSame('field %s does not match', $assert->getMessage());
    }
}