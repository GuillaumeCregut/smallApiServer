<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Bin\Database;

use Exception;
use App\Bin\ConsoleHelper;

class MakeEntityFile
{
    public function __construct(private string $appPath, private array $types, private array $relations, private array $constraints) {}

    public function createEntityFile(string $name, array $propertiesArray, array $fieldRelations): bool
    {
        $content = $this->getTemplatEntity($name, $propertiesArray, $fieldRelations);
        $folder = $this->appPath . 'src' . DIRECTORY_SEPARATOR . 'Entity';
        $filename = "{$name}Entity.php";
        return ConsoleHelper::saveToFile($folder, $filename, $content);
    }

    private function getTemplatEntity(string $name, array $propertiesArray, array $fieldRelations): string
    {
        $repoName = "{$name}Repository";
        $entityName = "{$name}Entity";
        $properties = "    #[NotStored]\n";
        $properties .= "    protected static ?string \$repo = {$repoName}::class;\n";
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
            if ('r' === $type) {
                if (empty($fieldRelations)) {
                    throw new Exception("Error : property {$key} set to relation, but no relations found");
                }
                $relation = $fieldRelations[$key];

                switch ($relation['type']) {
                    case 'm':
                        $uses['ManyToOne'] = "use App\\Kernel\\Connector\\Attributes\\ManyToOne;\n";
                        $target = $relation['foreign'];
                        $type = $target;
                        $updateConstraint =$relation['update'];
                        $deleteConstraint =$relation['delete'];
                        $fieldTarget = $relation['field'];
                        $properties .= "    #[ManyToOne(targetEntity: {$target}::class, inversedBy:'{$fieldTarget}', onUpdate:'{$this->constraints[$updateConstraint]}', onDelete:'{$this->constraints[$deleteConstraint]}')]\n";
                        break;
                    default : throw new Exception("Error : Incorrect relation for {$key}");
                }
            }
            $special = true;
            if(array_key_exists($type, $this->types)) {
                $displayType = $this->types[$type];
                $special = false;
            } else{
                $displayType = $type;
            }
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
            if($special){
                $gettersSetters .= "        \$this->syncRelation('{$key}',\${$key});\n";
            }
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
}
