<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Form\Validator\Assert\Min;
use App\Kernel\Form\Validator\Assert\MoreOrEquals;

class MinTest extends TestCase
{
    public function testMaxExtendsLessOrEquals(): void
    {
        $this->assertInstanceOf(MoreOrEquals::class, new Min(0));
    }
}
