<?php

namespace App\bin;


class Debug extends AbstractConsole
{
    protected string $spaceName ='App\\bin\\Debug\\';
    protected string $directory = __DIR__ . DIRECTORY_SEPARATOR . 'Debug';

    public function __construct(array $args, int $count)
    {
        $cmd= ConsoleHelper::makeSpecial("\$cmd", 'blue', 'reverse');
        $this->helpText =<<<TEXT
        Using debug : php ./bin/console debug $cmd
        for help type php ./bin/console debug help
        List of available commands :
        TEXT;
        parent::__construct($args, $count);
    }
    public function execute():void
    {
        $console = $this->isCommandValid($this->args[0]);
        if($console) {
            $console->execute();
        }
    }
}