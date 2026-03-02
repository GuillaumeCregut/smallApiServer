<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Bin\Database;

use App\Bin\ConsoleException;
use App\Bin\ConsoleHelper;
use App\Kernel\GetEnvDatas;
use App\Kernel\Connector\Interfaces\EntityInterface;
use App\Kernel\Connector\Interfaces\RepositoryInterface;
use App\Kernel\Connector\DatabaseException;
class CreateSql
{
    private string $appPath;

    public function __construct()
    {
        $this->appPath = GetEnvDatas::getAppPath();
    }
    public function execute(string $arg): void
    {
        $entities = [];
        if ('all' === $arg) {
            //do somehing different
            $entities = $this->getAllEntities();
        } else {
            $entityNS = 'App\\Entity\\';
            $class = ucfirst($arg);
            if ('user' === $arg) {
                $classname = 'App\\Security\\User';
            } else {
                $classname = $entityNS . $class;
            }
            $entities[$class] = $classname;
        }
        $sqls = [];
        try {
            foreach ($entities as $name => $Entityname) {
                $entity = $this->checkEntity($Entityname);
                $sql = $this->getSqlCreate($entity, $Entityname);
                $sqls[$name] = $sql;
            }
            $this->displaySQL($sqls);
        } catch (DatabaseException $e) {
            echo $e->getMessage();
            die();
        }
    }
    private function checkEntity(string $classname): ?EntityInterface
    {
        if (!class_exists($classname)) {
            throw new ConsoleException("Entity '{$classname}' does not exists");
        }
        $entity = new $classname();
        if (!$entity instanceof EntityInterface) {
            throw new ConsoleException("Entity '{$classname}' is not an Entity Class (must implements EntityInterface");
        }
        return $entity;
    }

    private function getSqlCreate(EntityInterface $entity, string $classname): string
    {
        $repoName = $entity->getRepository();
        if ((null === $repoName)) {
            throw new ConsoleException("Entity {$classname} has no repository set, please set it before with NotStored attribute");
        }
        if (!class_exists($repoName)) {
            throw new ConsoleException("Repository '{$repoName}' of entity {$classname} does not exists, check entity");
        }
        $repo = new $repoName();
        /**
         * @var RepositoryInterface $repo
         */
        if (!$repo instanceof RepositoryInterface) {
            throw new ConsoleException("Repository '{$repoName}' is not implementing RepositoryInterace");
        }
        return $repo->createSqlTable();
    }

    private function displaySQL(array $sqls): void
    {
        foreach ($sqls as $class => $sql) {
            echo "\n";
            echo "SQL for creating table for entity {$class} \n";
            echo $sql . "\n";
        }

        $question = "Do you want to save in file (migrations/) \033[1;31m ? \033[0m " . "(y/n)";
        $answer = '';

        do {
            $answer = strtolower(ConsoleHelper::ask($question));
        } while (('y' !== $answer) && ('n' !== $answer) && ('yes' !== $answer) && ('no' !== $answer));

        if (('y' === $answer) || ('yes' === $answer)) {
            $folder = $this->appPath . DIRECTORY_SEPARATOR . 'migrations';
            $nowDate =  ConsoleHelper::getDateTime();
            $now = $nowDate->format('Y/m/d H:i:s');
            $filename = 'sql' . $nowDate->format('YmdHis') . '.sql';
            $content = <<<QUERY
            -- SQL Entities table creation 
            -- Date : $now

            QUERY;
            foreach ($sqls as $class => $sql) {
                $content .= "-- SQL for creating table for entity {$class} \n";
                $content .= $sql . "\n";
            }
            if (ConsoleHelper::saveToFile($folder, $filename, $content)) {
                echo "file {$filename} write successully.";
            } else {
                echo "An error occures when writing {$filename}";
            }
        }
    }

    private function getAllEntities(): array
    {
        $namespace = 'App\\Entity\\';
        $folder = $this->appPath . 'src' . DIRECTORY_SEPARATOR . 'Entity' . DIRECTORY_SEPARATOR;
        $entities = [];
        $entities['User'] = 'App\\Security\\User';
        $files = glob($folder . "*.php");
        foreach ($files as $filename) {
            $name = basename($filename, '.php');
            $classname = $namespace . $name;
            $entities[$name] = $classname;
        }
        return $entities;
    }
}
