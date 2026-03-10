<?php

use App\Kernel\Form\Validator\Assert\MoreOrEquals;
use PHPUnit\Framework\TestCase;
use App\Kernel\Form\Validator\Assert\PositiveOrZero;

class PositiveOrZeroTest extends TestCase
{
    public function testExtendsMoreThan(): void
    {
        $this->assertInstanceOf(MoreOrEquals::class, new PositiveOrZero());
    }

    public function testReturnsTrueOnPositiveValue(): void
    {
        $this->assertTrue((new PositiveOrZero())->validate(1));
    }

    public function testReturnsFalseOnZero(): void
    {
        $this->assertTrue((new PositiveOrZero())->validate(0));
    }

    public function testReturnsFalseOnNegativeValue(): void
    {
        $this->assertFalse((new PositiveOrZero())->validate(-1));
    }

    public function testNullPassesThrough(): void
    {
        $this->assertTrue((new PositiveOrZero())->validate(null));
    }

    public function testDefaultMessageIsSet(): void
    {
        $this->assertSame('property %s must be positive or zero', (new PositiveOrZero())->getMessage());
    }

    public function testCustomMessageOverridesDefault(): void
    {
        $this->assertSame('custom', (new PositiveOrZero('custom'))->getMessage());
    }
}