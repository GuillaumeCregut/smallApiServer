<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Bin\Database;

use App\Bin\ConsoleException;
use App\Bin\ConsoleHelper;
use App\Kernel\GetEnvDatas;


class CreateEntity
{
    private string $appPath;
    private array $types = [
        's' => 'string',
        'i' => 'int',
        'dt' => 'DateTimeImmutable',
        'd' => 'DateTime',
        'f' => 'float',
        'b' => 'bool',
        'a' => 'array',
    ];

    public function __construct()
    {
        $this->appPath = GetEnvDatas::getAppPath();
    }

    public function execute(string $arg): void
    {
        if (!(preg_match('/^[a-zA-Z]+$/', $arg) === 1)) {
            throw new ConsoleException("{$arg} is not a valid name");
        }
        $entityName = ucfirst($arg);
        $isEntityExists = $this->checkEntityExists($entityName);
        if($isEntityExists || 'User' === $entityName){
            $special = ConsoleHelper::makeSpecial('Erreur :', 'red', 'bold');
            echo "{$special} Entity {$entityName} already exists. Make change manually.";
            return;
        }
        $properties = [];
        $action = '';
        do {
            $propertyName = $this->askPropertyName();
            $propertyType = $this->askPropertyType();
            $stored = $this->askYesNo('Is porperty stored in DB ? (y/n)');
            $nullable = $this->askYesNo('Is porperty nullable ? (y/n)');
            $displayNull = $nullable ? '' : 'not ';
            $displayStore = $stored ? '' : 'not ';
            $display = "Poperty {$propertyName}, type {$this->types[$propertyType]} {$displayNull}null {$displayStore}stored in DB\n";
            echo $display;

            $ok = $this->askYesNo('Is this OK ? (y/n)');
            if ($ok) {
                $properties[$propertyName] = [
                    'type' => $propertyType,
                    'stored' => $stored,
                    'nullable' => $nullable
                ];
            } else {
                echo "Property not saved.\n";
            }

            $action = ConsoleHelper::ask('Add a new Property ? (n for stop) ');
        } while ('n' !== $action);
        echo "\n";

        echo "Those infomations will be save into {$entityName} : \n";
        foreach ($properties as $key => $values) {
            $displayNull = $values['nullable'] ? '' : 'not ';
            $displayStore = $values['stored'] ? '' : 'not ';
            $type = $values['type'];
            $display = "Poperty {$key}, type {$this->types[$type]} {$displayNull}null {$displayStore}stored in DB\n";
            echo $display;
        }
        $answer = $this->askYesNo('Create files ? (y/n)');
        if ($answer) {
            $this->createFiles($entityName, $properties);
        }
    }

    private function askPropertyName(): string
    {
        $name = '';
        do {
            $name = ConsoleHelper::ask('Name of the property (ex: price) ');
            if (!(preg_match('/^[a-zA-Z]+$/', $name) === 1)) {
                $error = ConsoleHelper::makeSpecial('Error', 'red', 'bold');
                echo "{$error} {$name} is not a valid name\n";
            }
        } while (!(preg_match('/^[a-zA-Z]+$/', $name) === 1));
        $name = lcfirst($name);
        return $name;
    }
    private function askPropertyType(): string
    {
        $list = "Type of property :\n";
        foreach ($this->types as $key => $value) {
            $list .= "{$key} : {$value}\n";
        }
        echo $list;
        do {
            $type = ConsoleHelper::ask('Type of the property (ex: f for float) ');
            if (!array_key_exists($type, $this->types)) {
                $error = ConsoleHelper::makeSpecial('Error', 'red', 'bold');
                echo "{$error} {$type} is not a valid type\n";
            }
        } while (!array_key_exists($type, $this->types));
        return $type;
    }

    private function askYesNo(string $question): bool
    {
        $responseValues = [
            'y',
            'n',
            'no',
            'yes'
        ];
        do {
            $response = ConsoleHelper::ask($question);
        } while (!in_array($response, $responseValues));
        return (($response === 'y') || ($response === 'yes'));
    }

    private function createFiles(string $name, array $propertiesArray): void
    {
        $entityCreate = $this->createEntityFile($name, $propertiesArray);
        if ($entityCreate) {
            $ok = ConsoleHelper::makeSpecial('Entity create successfully...','green','reset');
            echo "$ok\n";
            echo "Create repository\n";
            $repositoryCreate = $this->createRepositoryFile($name);
            if ($repositoryCreate) {
                $ok = ConsoleHelper::makeSpecial('Repository create successfully...', 'green', 'reset');
                echo "$ok\n";
                return;
            }
            $error = ConsoleHelper::makeSpecial('Error', 'red', 'bold');
            echo "{$error} : Repository not created\n";
            return;
        }
        $error = ConsoleHelper::makeSpecial('Error', 'red', 'bold');
        echo "{$error} : Entity not created. Aborting\n";
        return;
    }

    private function createEntityFile(string $name, array $propertiesArray): bool
    {
        $content = $this->getTemplatEntity($name, $propertiesArray);
        $folder = $this->appPath . 'src' . DIRECTORY_SEPARATOR . 'Entity';
        $filename = "{$name}Entity.php";
        return ConsoleHelper::saveToFile($folder, $filename, $content);
    }

    private function createRepositoryFile(string $name): bool
    {
        $content = $this->getRepositoryTemplate($name);
        $folder = $this->appPath . 'src' . DIRECTORY_SEPARATOR . 'Repository';
        $filename = "{$name}Repository.php";
        return ConsoleHelper::saveToFile($folder, $filename, $content);
    }

    private function getTemplatEntity(string $name, array $propertiesArray): string
    {
        $repoName = "{$name}Repository";
        $entityName = "{$name}Entity";
        $properties = "    #[NotStored]\n";
        $properties .= "    protected ?string \$repo = {$repoName}::class;\n";
        $gettersSetters = "";
        $uses['repo'] = "use App\\Repository\\{$repoName};\n";
        $uses['stored'] = "use App\\Kernel\\Connector\\Attributes\\NotStored;\n";
        foreach ($propertiesArray as $key => $values) {
            if (!$values['stored']) {
                $properties .= "    #[NotStored]\n";
                $uses['stored'] = "use App\\Kernel\\Connector\\Attributes\\NotStored;\n";
            }
            if ($values['nullable']) {
                $properties .= "    #[Nullable]\n";
                $uses['nullable'] = "use App\\Kernel\\Connector\\Attributes\\Nullable;\n";
            }
            $type = $values['type'];
            $displayType = $this->types[$type];
            $properties .= "    private ?{$displayType} \${$key} = null;\n";

            $getterName = 'get' . ucfirst($key);
            $gettersSetters .= "    public function {$getterName}(): {$displayType}\n";
            $gettersSetters .= "    {\n";
            $gettersSetters .= "        return \$this->{$key};\n";
            $gettersSetters .= "    }\n\n";

            $setterName = 'set' . ucfirst($key);
            $gettersSetters .= "    public function {$setterName}($displayType \${$key}): self\n";
            $gettersSetters .= "    {\n";
            $gettersSetters .= "        \$this->{$key} = \${$key};\n";
            $gettersSetters .= "        return \$this;\n";
            $gettersSetters .= "    }\n\n";
        }
        $useDisplay = '';
        foreach ($uses as $use) {
            $useDisplay .= "{$use}";
        }
        $template = <<<PHP
<?php

namespace App\Entity;

use App\Kernel\Connector\AbstractEntity;

$useDisplay
class $entityName extends AbstractEntity
{

{$properties}
{$gettersSetters}
}
PHP;
        return $template;
    }

    private function getRepositoryTemplate(string $name): string
    {
        $repoName = "{$name}Repository";
        $entityName = "{$name}Entity";
        $entityClass = "use App\\Entity\\$entityName;";
        $template = <<<PHP
<?php

namespace App\Repository;

use App\Kernel\Connector\AbstractRepository;
$entityClass

class $repoName extends AbstractRepository
{
    protected ?string \$entity = $entityName::class;
}
PHP;
        return $template;
    }

    private function  checkEntityExists($name): bool
    {
        $entityName = "{$name}Entity";
        $path = $this->appPath .'src' . DIRECTORY_SEPARATOR. 'Entity' . DIRECTORY_SEPARATOR;
        $filename = "{$path}{$entityName}.php";
        return file_exists($filename);
    }
}
