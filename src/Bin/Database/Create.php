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
        - create:help - Display this message
        - create:entity entity - Create a new entity whit name Entity and Repository (ex: create:entity product will create Product entity)
        - create:migration - Create migration file, updating database to correspond Entities schema
        - create:sql entity - Display Create Table for entity (all for all entities)

        TEXT;
        if (($this->minArgs > $count) || ('' === $args[0])) {
            if ('help' === $args[0]) {
                $this->help(false);
            }
            $this->error('Too few arguments');
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
            case 'entity':
                $this->entity($arg);
                break;
            case 'help':
                $this->help(false);
                break;
            case 'migration':
                $this->migration($arg);
                break;
            default:
                $this->error("Command {$cmd} does not exist");
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

    private function migration(string $arg): void
    {
        $creator = new CreateMigration();
        try {
            $creator->execute();
        } catch(ConsoleException $e) {
             $this->error($e->getMessage(), false);
        }
    }

    private function sql(string $arg): void
    {
        $creator = new CreateSql();
        try {
            $creator->execute($arg);
        } catch (ConsoleException $e) {
            $this->error($e->getMessage(), false);
        }
    }
    private function entity(string $arg): void
    {
       $creator = new CreateEntity();
       try {
            $creator->execute($arg);
       } catch(ConsoleException $e){
        $this->error($e->getMessage(), false);
       }
    }
}
