<?php

use App\Kernel\Form\Validator\Assert\Email;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function testPassesWithNullValue(): void
    {
        $email = new Email();
        $this->assertTrue($email->validate(null));
    }

    public function testFailsOnEmptyValue(): void
    {
        $email = new Email();
        $this->assertFalse($email->validate(''));
    }

    public function testFailsOnBadEmail(): void
    {
        $email = new Email();
        $this->assertFalse($email->validate('foo'));
    }

    public function testPassWithValidEmailValue(): void
    {
        $email = new Email();
        $this->assertTrue($email->validate('foo@bar.com'));
    }
}