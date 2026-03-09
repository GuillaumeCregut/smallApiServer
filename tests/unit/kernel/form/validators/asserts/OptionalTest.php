<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Form\Validator\Assert\Optional;

class OptionalTest extends TestCase
{
    public function testOptionalValidateAlwaysReturnsTrue(): void
    {
        $optional = new Optional();
        $this->assertTrue($optional->validate(null));
        $this->assertTrue($optional->validate('some value'));
        $this->assertTrue($optional->validate(42));
        $this->assertTrue($optional->validate(''));
    }
}
