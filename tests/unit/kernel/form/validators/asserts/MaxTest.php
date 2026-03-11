<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Form\Validator\Assert\Max;
use App\Kernel\Form\Validator\Assert\LessOrEquals;

class MaxTest extends TestCase
{
    public function testMaxExtendsLessOrEquals(): void
    {
        $this->assertInstanceOf(LessOrEquals::class, new Max(0));
    }
}
