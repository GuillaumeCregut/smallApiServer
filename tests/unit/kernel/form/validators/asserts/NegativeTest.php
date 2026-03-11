<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Form\Validator\Assert\LessThan;
use App\Kernel\Form\Validator\Assert\Negative;


class NegativeTest extends TestCase
{
    public function testExtendsLessThan(): void
    {
        $this->assertInstanceOf(LessThan::class, new Negative());
    }

    public function testReturnsTrueOnNegativeValue(): void
    {
        $this->assertTrue((new Negative())->validate(-1));
    }

    public function testReturnsFalseOnZero(): void
    {
        $this->assertFalse((new Negative())->validate(0));
    }

    public function testReturnsFalseOnPositiveValue(): void
    {
        $this->assertFalse((new Negative())->validate(1));
    }

    public function testNullPassesThrough(): void
    {
        $this->assertTrue((new Negative())->validate(null));
    }

    public function testDefaultMessageIsSet(): void
    {
        $this->assertSame('property %s must be negative', (new Negative())->getMessage());
    }

    public function testCustomMessageOverridesDefault(): void
    {
        $this->assertSame('custom', (new Negative('custom'))->getMessage());
    }
}