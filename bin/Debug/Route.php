<?php

namespace App\bin\Debug;

use App\bin\AbstractConsole;
use App\Kernel\Config\Router;

class Route extends AbstractConsole
{
    protected int $minArgs = 0;

    public function execute(): void
    {
        $this->displayHelp();
        $routes = $this->getRoutes();
        $headers = [
            'URI',
            'METHOD',
            'CONTROLLER',
            'FUNCTION'
        ];
        $arrayDisplay = [];
        foreach ($routes as $route => $params) {
            $routeLines = $this->makeLines($route, $params);
            $arrayDisplay = array_merge($arrayDisplay,$routeLines);
        }
        
        $this->displayArray($headers, $arrayDisplay);
    }

    private function displayArray(array $headers, array $rows): void
    {
        echo "\nHere are routes used by SmallApiServer : \n\n";
        $widths = $this->calculateSize($headers, $rows);
      //  var_dump($widths);
        $separator = $this->makeSeparator($widths);
        echo $separator;
        echo $this->displayLines($headers, $widths);
        echo $separator;
        foreach($rows as $row){
            echo $this->displayLines($row, $widths);
        }
        echo $separator;
    }

    private function displayHelp(): void
    {
        $help = $this->args[0] ?? '';
        if ('help' === $help) {
            $this->helpText = <<<TEXT
            this command display routes used by SmallApiServer.
            TEXT;
            $this->help(false);
        }
    }

    private function getRoutes(): array
    {
        $routes = Router::getRoutes();
        return $routes;
    }

    private function makeLines(string $route, array $routeParam): array
    {
        foreach ($routeParam as $key => $value) {
            $controller = basename(str_replace('\\','/',$value[0])); 
            $line = [
                $route . '/',
                $key,
                $controller,
                $value[1]
            ];
            $tempLine[] = $line;
        }
        return $tempLine;
    }

    private function calculateSize(array $header, array $lines): array
    {
        $widths = array_map('strlen', $header);
        foreach ($lines as $line) {
            foreach ($line as $i => $cell) {
                $widths[$i]= max($widths[$i], strlen((string)$cell));
            }
        }
        return  $widths;
    }

    private function makeSeparator(array $widths): string
    {
        $line = '+';
        foreach($widths as $width){
            $line .= str_repeat('-',$width+1) . '+';
        }
        return $line ."\n";
    }

    private function displayLines(array $row, array $widths): string
    {
        $line = '|';
        foreach($row as $i => $cell) {
            $line .=' ' . str_pad((string) $cell, $widths[$i]) . '|';
        }
        return $line . "\n";
    }
}
