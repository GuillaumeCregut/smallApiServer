<?php

use App\Kernel\Form\Validator\Assert\NotNull;
use PHPUnit\Framework\TestCase;

class NotNullTest extends TestCase
{
    public function testReturnFalseIfNull(): void
    {
        $notNull = new NotNull();
        $this->assertFalse($notNull->validate(null));
    }

    public function testReturnTrueIfNotNull(): void
    {
        $notNull = new NotNull();
        $this->assertTrue($notNull->validate(''));
        $this->assertTrue($notNull->validate(0));
        $this->assertTrue($notNull->validate(false));
        $this->assertTrue($notNull->validate(1));
    }
}