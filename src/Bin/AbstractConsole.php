<?php

namespace App\Bin;

use App\Bin\ConsoleHelper;
use Error;
use Exception;

abstract class AbstractConsole implements ConsoleInterface
{
    protected string $helpText = '';
    protected string $spaceName = '';
    protected string $directory = __DIR__;
    protected int $minArgs = 1;
    protected array $args;

    public function __construct(array $args, int $count)
    {
        if ($this->minArgs > $count) {
            $this->displayError('Too few arguments', 0);
        }
        $this->args = $args;
    }

    abstract public function execute(): void;

    protected function help(?bool $displayCmd = true): void
    {
        echo $this->helpText;
        if ($displayCmd) {
            $cmds = ConsoleHelper::getCommands($this->spaceName, $this->directory);
            echo "\n";
            if (0 === count($cmds)) {
                echo "- No commands found";
            }
            foreach ($cmds as $cmd) {
                echo "- {$cmd}\n";
            }
        }

        die();
    }

    protected function displayError(string $error, int $code): void
    {
        $start = ConsoleHelper::makeSpecial('Error :', 'red', 'bold');
        echo "{$start} {$error} \n";
        $this->help();
    }

    protected function isCommandValid(string $command): ConsoleInterface | false
    {
        $first = strtolower($command);
        if ('help' === $first) {
            $this->help();
        }
        $class = ucfirst($first) ?? null;
        $fullname = $this->spaceName . $class;
        if (! class_exists($fullname)) {
            $this->displayError("Command {$first} does not exists", 0);
        }

        $args = array_slice($this->args, 1);
        $count = count($args);
        try {
            $console = new $fullname($args, $count);
            if (!($console instanceof ConsoleInterface)) {
                return false;
            }
            return $console;
        } catch (Exception $e) {
            return false;
        } catch (Error $e) {
            return false;
        }
    }
}
