<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Bin\Database;

use Exception;
use App\Security\User;
use App\Kernel\GetEnvDatas;
use App\Bin\ConsoleException;
use App\Kernel\Config\DatabaseConnector;
use App\Kernel\Connector\ConnectorDispatcher;
use App\Kernel\Connector\Utils\EntityAnalyzer;
use App\Kernel\Connector\Utils\SchemaComparator;
use App\Kernel\Connector\Interfaces\ConnectorInterface;
use App\Kernel\Connector\Utils\EntitySchemaTransformer;
use App\Kernel\Connector\Utils\Scanner\DatabaseScanner;
use App\Kernel\Connector\Utils\Migration\MigrationWriter;
use App\Kernel\Connector\Utils\Migration\MigrationGenerator;

class CreateMigration
{
    private ConnectorInterface $connector;

    public function __construct()
    {
        try {
            ConnectorDispatcher::setConnector(DatabaseConnector::getConnector());
        } catch (Exception $e) {
            echo $e->getMessage();
            echo "Code : {$e->getCode()}";
            die();
        }
    }
    public function execute(): void
    {
        echo "- gathering Entities informations\n";
        try {
            $path = GetEnvDatas::getAppPath() . 'src' . DIRECTORY_SEPARATOR . 'Entity' . DIRECTORY_SEPARATOR;
            $entities = EntityAnalyzer::getAllEntitiesProperties($path);
            $userProperties = EntityAnalyzer::getStoredProperties(User::class, true);
            $entities['users'] = $userProperties;
        } catch (Exception $e) {
            throw new ConsoleException($e->getMessage());
        }

        echo "- Transforming Entities informations\n";
        try {
            $newEntities = [];
            foreach ($entities as $name => $entity) {
                $transformer = new EntitySchemaTransformer();
                $transormed = $transformer->transform($name, $entity);
                $newEntities[$name] = $transormed;
            }
        } catch (Exception $e) {
            throw new ConsoleException($e->getMessage());
        }

        echo "- Gathering database informations";
        try {
            $this->connector = ConnectorDispatcher::getConnector();
            $scanner = new DatabaseScanner($this->connector);
            $dbSchema = $scanner->scan();
        } catch (Exception $e) {
            throw new ConsoleException($e->getMessage());
        }

        echo "- Comparing schemas\n";
        try {
            $comparator = new SchemaComparator();
            $diff = $comparator->compare($newEntities, $dbSchema);
        } catch (Exception $e) {
            throw new ConsoleException($e->getMessage());
        }

        echo "- Generating queries\n";
        try {
            $generator = new MigrationGenerator($this->connector);
            $sql = $generator->generate($diff);
        } catch (Exception $e) {
            throw new ConsoleException($e->getMessage());
        }

        echo "- Generating migration file\n";
        try {
            $rootPath = GetEnvDatas::getAppPath();
            $file = new MigrationWriter($rootPath);
            $file = $file->write($sql);
        } catch (Exception $e) {
            throw new ConsoleException($e->getMessage());
        }
        if(null === $file) {
            echo "No difference between entities and database, no file created\n";
            return;
        }
        echo "File {$file} created successfully. Please check it and modify it if necessary.\n";
        echo "When you are ready to apply changes in database, please run ./bin/console database migrate:up";
    }
}
