<?php
namespace App\Bin;

interface ConsoleInterface
{
    public function __construct(array $args, int $count);
    public function execute(): void;
}