<?php

use App\Kernel\Form\Validator\Assert\LessOrEquals;
use PHPUnit\Framework\TestCase;

use App\Kernel\Form\Validator\Assert\NegativeOrzero;


class NegativeOrzeroTest extends TestCase
{
    public function testExtendsLessThan(): void
    {
        $this->assertInstanceOf(LessOrEquals::class, new NegativeOrzero());
    }

    public function testReturnsTrueOnNegativeValue(): void
    {
        $this->assertTrue((new NegativeOrzero())->validate(-1));
    }

    public function testReturnsTrueOnZero(): void
    {
        $this->assertTrue((new NegativeOrzero())->validate(0));
    }

    public function testReturnsFalseOnPositiveValue(): void
    {
        $this->assertFalse((new NegativeOrzero())->validate(1));
    }

    public function testNullPassesThrough(): void
    {
        $this->assertTrue((new NegativeOrzero())->validate(null));
    }

    public function testDefaultMessageIsSet(): void
    {
        $this->assertSame('property %s must be negative or zero', (new NegativeOrzero())->getMessage());
    }

    public function testCustomMessageOverridesDefault(): void
    {
        $this->assertSame('custom', (new NegativeOrzero('custom'))->getMessage());
    }
}