<?php

namespace App\Kernel\Interfaces;
interface ResponseInterface
{
    public function setStatusCode(int $code): void;
    public function setHeader(string $name, string $value): void;
    public function setBody(string $content): void;
    public function send(): string;
}