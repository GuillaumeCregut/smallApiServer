<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Bin\Database;

use App\Bin\ConsoleHelper;
use App\Bin\AbstractConsole;
use App\Bin\ConsoleException;

class Create extends AbstractConsole
{
    protected string $spaceName = 'App\\Bin\\Database\\';
    protected string $directory = __DIR__ . DIRECTORY_SEPARATOR . 'Database';
    protected int $minArgs = 2;

    public function __construct(array $args, int $count)
    {
        $cmd = ConsoleHelper::makeSpecial("command", 'blue', 'reverse');
        $this->helpText = <<<TEXT

        Using database : php ./bin/console database create:$cmd params
        for help type php ./bin/console database create:help

        List of available commands :
        - create:sql entity - Display Create Table for entity (all for all entities)

        TEXT;
        if (($this->minArgs > $count) || ('' === $args[0])) {
            $this->error('Too few arguments');
        } {
            if ('help' === $args[0]) {
                $this->help(false);
            }
        }
        $this->args = $args;
    }

    public function execute(): void
    {
        $cmd = $this->args[0];
        $arg = $this->args[1];
        switch ($cmd) {
            case 'sql':
                $this->sql($arg);
                break;
            default:
                $this->error('Command does not exist');
        }
    }
    private function error(string $error, ?bool $displayHelp = true): void
    {
        $start = ConsoleHelper::makeSpecial('Error :', 'red', 'bold');
        echo "{$start} {$error} \n";
        if ($displayHelp) {
            $this->help(false);
        }
        die();
    }
    private function sql(string $arg): void
    {
        $creator = new CreateSql();
        try {
            $creator->execute($arg);
        } catch(ConsoleException $e){
             $this->error($e->getMessage(), false);
        }
    }
}
