<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Bin;

use App\Kernel\Config\DatabaseConnector;
use App\Kernel\Connector\ConnectorDispatcher;
use Exception;

class Database extends AbstractConsole
{
    protected string $spaceName = 'App\\Bin\\Database\\';
    protected string $directory = __DIR__ . DIRECTORY_SEPARATOR . 'Database';
    protected int $minArgs = 1;

    public function __construct(array $args, int $count)
    {
        $cmd = ConsoleHelper::makeSpecial("\$cmd", 'blue', 'reverse');
        $this->helpText = <<<TEXT
        Using database : php ./bin/console database $cmd params
        for help type php ./bin/console database help
        List of available commands :
        TEXT;
        parent::__construct($args, $count);
    }
    public function execute(): void
    {
        //Command will be like create:table, create:entity...
        $commands = explode(':', $this->args[0]);
        if (count($commands) !== 2) {
            $cmd = ConsoleHelper::makeSpecial('command', 'blue', 'bold');
            throw new Exception("Check your command\n Must be {$cmd}:arg, try {$cmd}:help");
        }
        $command = $commands[0];
        $this->args = array_slice($this->args, 1);
        array_unshift($this->args, $commands[1]);
        $console = $this->isCommandValid($command, false);
        try {
            ConnectorDispatcher::setConnector(DatabaseConnector::getConnector());
        } catch (Exception $e) {
            echo $e->getMessage();
            die();
        }
        if ($console) {
            $console->execute();
        }
    }
}
