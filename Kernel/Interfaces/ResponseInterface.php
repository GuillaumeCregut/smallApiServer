<?php

namespace App\Kernel\Interfaces;

interface ResponseInterface
{
    public function setStatusCode(int $code): self;
    public function setHeader(string $name, string $value): self;
    public function setBody(string $content): self;
    public function setCookie(string $name, string $value, ?array $params=[]): self;
    public function send(): string;
}
