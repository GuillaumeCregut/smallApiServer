<?php

use App\Kernel\Form\Validator\Assert\NotBlank;
use PHPUnit\Framework\TestCase;

class NotBlankTest extends TestCase
{
    public function testReturnFalseIfBlank(): void
    {
        $notBlank = new NotBlank();
        $this->assertFalse($notBlank->validate(''));
        $this->assertFalse($notBlank->validate(" "));
    }

    public function testReturnTrueIfNotBlank(): void
    {
        $notBlank = new NotBlank();
        $this->assertTrue($notBlank->validate('John'));
        $this->assertTrue($notBlank->validate(null));
    }

    public function testWillReturnFalseNotAllowedTypes(): void
    {
        $notBlank = new NotBlank();
        $this->assertFalse($notBlank->validate(0));
        $this->assertFalse($notBlank->validate([]));
        $this->assertFalse($notBlank->validate(false));
        $this->assertFalse($notBlank->validate(true));
    }
}