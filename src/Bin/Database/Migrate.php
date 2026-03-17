<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Bin\Database;

use App\Bin\ConsoleHelper;
use App\Kernel\GetEnvDatas;
use App\Bin\AbstractConsole;
use App\Kernel\Connector\ConnectorDispatcher;
use App\Kernel\Connector\Utils\Migration\MigrationRunner;
use App\Kernel\Exceptions\KernelException;
use Exception;

class Migrate extends AbstractConsole
{
    protected int $minArgs = 1;
    private MigrationRunner $runner;

    public function __construct(array $args, int $count)
    {
        $cmd = ConsoleHelper::makeSpecial("command", 'blue', 'reverse');
        $this->helpText = <<<TEXT

        Using database : php ./bin/console database migrate:$cmd params
        for help type php ./bin/console database migrate:help

        List of available commands :
        - migrate:help - Display this message
        - migrate:up - Play all non applyied migrations
        - migrate:down - Not yet implemented
        - migrate:status - Display informations on migrations

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
        try {
            $connector = ConnectorDispatcher::getConnector();
            $rootPath = GetEnvDatas::getAppPath();
            $this->runner = new MigrationRunner($connector, $rootPath);
        } catch (Exception $e) {
            echo $e->getMessage();
            die();
        }
        $cmd = $this->args[0];
        switch ($cmd) {
            case 'help':
                echo " au secours";
                $this->help(false);
                break;
            case 'up':
                $this->up();
                break;
            case 'down':
                $arg = $this->args[1] ?? null;
                $this->down($arg);
                break;
            case 'status':
                $this->status();
                break;
            default:
                $this->error("Command {$cmd} does not exist");
        }
    }

    private function up(): void
    {
        echo "These migrations will be apply to database :\n";
        $count =$this->status(true);
        if(0 === $count) {
            echo "No new migrations pendings. Ending\n";
            return;
        }
        $question = "Is this OK ? (y/n)";
        $response = ConsoleHelper::askWhile($question, ['y', 'n']);
        if ('y' !== $response) {
            return;
        }
        echo "Migrating database\n";
        try {
            $result = $this->runner->migrate();
            if (empty($result)) {
                echo "No migrations done.\n";
                return;
            }
            echo "Those migrations add been applied successfully\n";
            foreach($result as $version) {
                echo "- {$version}\n";
            }
        } catch (Exception $e) {
            $error = ConsoleHelper::makeSpecial('Error', 'red', 'bold');
            echo "{$error} : {$e->getMessage()}\n";
        }
    }

    private function down(?string $version): void
    {
        echo "Command down not yet implemented\n";
    }

    private function status(bool $filter = false): int
    {
        $count =0;
        $status = $this->runner->status();
        echo "Status of migrations :\n";
        foreach ($status as $version => $state) {
            if($filter && ('executed' === $state)) {
                continue;
            }
            $count++;
            echo "- Version : {$version} : {$state}\n";
        }
        return $count;
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
}
