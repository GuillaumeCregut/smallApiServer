<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Form\Validator\Assert\MoreThan;
use App\Kernel\Form\Validator\Assert\Positive;

class PositiveTest extends TestCase
{
    public function testExtendsMoreThan(): void
    {
        $this->assertInstanceOf(MoreThan::class, new Positive());
    }

    public function testReturnsTrueOnPositiveValue(): void
    {
        $this->assertTrue((new Positive())->validate(1));
    }

    public function testReturnsFalseOnZero(): void
    {
        $this->assertFalse((new Positive())->validate(0));
    }

    public function testReturnsFalseOnNegativeValue(): void
    {
        $this->assertFalse((new Positive())->validate(-1));
    }

    public function testNullPassesThrough(): void
    {
        $this->assertTrue((new Positive())->validate(null));
    }

    public function testDefaultMessageIsSet(): void
    {
        $this->assertSame('property %s must be positive', (new Positive())->getMessage());
    }

    public function testCustomMessageOverridesDefault(): void
    {
        $this->assertSame('custom', (new Positive('custom'))->getMessage());
    }
}