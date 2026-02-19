<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Bin\Database;

use App\Bin\ConsoleHelper;
use App\Bin\AbstractConsole;
use App\Kernel\Interfaces\Databases\EntityInterface;
use App\Kernel\Interfaces\Databases\RepositoryInterface;
use DateException;

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
        if($displayHelp){
            $this->help(false);
        }
        die();
    }
    private function sql(string $arg): void
    {
        $entityNS = 'App\\Entities\\';
        $class = ucfirst($arg);
        if ('user' === $arg) {
            $classname = 'App\\Security\\User';
        } else {
            $classname = $entityNS . $class;
        }
        if (!class_exists($classname)) {
            $this->error("Entity '{$classname}' does not exists", false);
        }
        try {
            $entity = new $classname();
            if (!$entity instanceof EntityInterface) {
                $this->error("Entity '{$classname}' is not an Entity Class (must implements EntityInterface", false);
            }
            /**
             * @var EntityInterface $entity
             */
            $repoName = $entity->getRepository();
            if(null === $repoName) {
                $this->error("Entity {$classname} has no repository set, please set it before with NotStored attribute", false);
            }
            $repo = new $repoName();
            /**
             * @var RepositoryInterface $repo
             */
            if (!$repo instanceof RepositoryInterface) {
                $this->error("Repository '{$repoName}' is not implementing RepositoryInterace", false);
            }
            echo "SQL for creating table for entity {$class} \n";
            echo $repo->createSqlTable();
        } catch (DateException $e) {
            echo $e->getMessage();
            die();
        }
    }
}
