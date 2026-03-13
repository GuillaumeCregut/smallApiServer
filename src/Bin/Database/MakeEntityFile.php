<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Bin\Database;

use App\Bin\ConsoleException;
use App\Bin\ConsoleHelper;

class MakeEntityFile
{
    private string $properties = '';
    private string $gettersSetters = '';
    private array $uses = [];

    public function __construct(private string $appPath, private array $types,  private array $constraints) {}

    public function createEntityFile(string $name, array $propertiesArray, array $fieldRelations): bool
    {
        $content = $this->getTemplatEntity($name, $propertiesArray, $fieldRelations);
        $folder = $this->appPath . 'src' . DIRECTORY_SEPARATOR . 'Entity';
        $filename = "{$name}.php";
        $result = ConsoleHelper::saveToFile($folder, $filename, $content);
        return $result;
    }

    private function getTemplatEntity(string $name, array $propertiesArray, array $fieldRelations): string
    {
        if (str_ends_with($name, 'Entity')) {
            $shortName = substr($name, 0, -6);
        }
        $repoName = "{$shortName}Repository";
        $entityName = "{$name}";
        $this->properties = "    #[NotStored]\n";
        $this->properties .= "    protected static ?string \$repo = {$repoName}::class;\n";
        $this->uses['repo'] = "use App\\Repository\\{$repoName};\n";
        $this->uses['stored'] = "use App\\Kernel\\Connector\\Attributes\\NotStored;\n";
        foreach ($propertiesArray as $property => $values) {
            $relation = $fieldRelations[$property] ?? [];
            $this->getProperty($property, $values, $relation);
        }
        return $this->makeTemplate($entityName);
    }

    private function getProperty(string $propertyName, array $property, array $relation): void
    {
        $inRelation = false;
        $newRelationType = '';
        $foreignClass = '';
        $relationType = null;
        if (!$property['stored']) {
            $this->properties .= "    #[NotStored]\n";
            $this->uses['stored'] = "use App\\Kernel\\Connector\\Attributes\\NotStored;\n";
        }
        if ($property['nullable']) {
            $this->properties .= "    #[Nullable]\n";
            $this->uses['nullable'] = "use App\\Kernel\\Connector\\Attributes\\Nullable;\n";
        }
        if (!empty($relation)) {
            $inRelation = true;
            $relationType = $relation['type'];
            try {
                $newRelationType = $this->makeHeaderRelation($relationType, $property, $relation);
            } catch (ConsoleException $e) {
                throw new ConsoleException("Error : Incorrect relation for {$propertyName}");
            }
        }
        $propertyTypeKey = $property['type'];
        if (array_key_exists($propertyTypeKey, $this->types)) {
            $propertyType = $this->types[$propertyTypeKey];
            if('fl' === $propertyTypeKey) {
                $this->uses['File'] = "use App\\Kernel\\Files\\FileUpload;\n";
            }
        } else {
            throw new ConsoleException("Type {$propertyTypeKey} for {$propertyName} does not exists");
        }
        if ($inRelation) {
            $foreignClass = $relation['foreign'];
            $propertyType =  $newRelationType;
        }
        $this->properties .= "    private ?{$propertyType} \${$propertyName} = null;\n";
        $this->makeGettersSetters($propertyName, $propertyType, $relationType);
        if ('o' === $relationType) {
            $this->makeRelationGettersSetters($propertyName, $propertyType, $foreignClass);
        }
    }

    private function makeTemplate(string $entityName): string
    {
        $useDisplay = '';
        foreach ($this->uses as $use) {
            $useDisplay .= "{$use}";
        }

        $template = <<<PHP
<?php

namespace App\Entity;

use App\Kernel\Connector\AbstractEntity;

$useDisplay
final class $entityName extends AbstractEntity
{

{$this->properties}
{$this->gettersSetters}
}
PHP;
        return $template;
    }



    private function makeRelationGettersSetters(string $propertyName, string $propertyType, string $elementType): void
    {
        $functionName = 'add' . ucfirst($propertyName);
        $this->gettersSetters .= "    public function {$functionName} ($elementType \${$propertyName}): self\n";
        $this->gettersSetters .= "    {\n";
        $this->gettersSetters .= "        \$this->{$propertyName}->addToCollection(\${$propertyName});\n";
        $this->gettersSetters .= "        return \$this;\n";
        $this->gettersSetters .= "    }\n\n";

        $functionName = 'getOne' . ucfirst($propertyName);
        $this->gettersSetters .= "    public function {$functionName} (int \$index): {$elementType}\n";
        $this->gettersSetters .= "    {\n";
        $this->gettersSetters .= "        return \$this->{$propertyName}->get(\$index);\n";
        $this->gettersSetters .= "    }\n\n";

        $functionName = 'remove' . ucfirst($propertyName);
        $this->gettersSetters .= "    public function {$functionName} ({$elementType} \${$propertyName}): self\n";
        $this->gettersSetters .= "    {\n";
        $this->gettersSetters .= "        \$this->{$propertyName}->remove(\${$propertyName});\n";
        $this->gettersSetters .= "        return \$this;\n";
        $this->gettersSetters .= "    }\n\n";
    }

    private function makeGettersSetters(string $propertyName, string $propertyType, ?string $relationType = null): void
    {
        $getterName = 'get' . ucfirst($propertyName);
        $this->gettersSetters .= "    public function {$getterName}(): {$propertyType}\n";
        $this->gettersSetters .= "    {\n";
        $this->gettersSetters .= "        return \$this->{$propertyName};\n";
        $this->gettersSetters .= "    }\n\n";
        $setterName = 'set' . ucfirst($propertyName);
        $this->gettersSetters .= "    public function {$setterName}($propertyType \${$propertyName}): self\n";
        $this->gettersSetters .= "    {\n";
        $this->gettersSetters .= "        \$this->{$propertyName} = \${$propertyName};\n";
        if ('m' === $relationType) {
            $this->gettersSetters .= "        \$this->syncRelation('{$propertyName}',\${$propertyName});\n";
        }
        $this->gettersSetters .= "        return \$this;\n";
        $this->gettersSetters .= "    }\n\n";
    }


    private function makeHeaderRelation(string $relationType, array $property, array $relation): string
    {
        $newPropertyType = '';
        switch ($relationType) {
            case 'm':
                $this->uses['ManyToOne'] = "use App\\Kernel\\Connector\\Attributes\\ManyToOne;\n";
                $target = $relation['foreign'];
                $updateConstraint = $relation['update'];
                $deleteConstraint = $relation['delete'];
                $fieldTarget = $relation['field'];
                $newPropertyType = $relation['foreign'];
                $this->properties .= "    #[ManyToOne(targetEntity: {$target}::class, inversedBy:'{$fieldTarget}', onUpdate:'{$this->constraints[$updateConstraint]}', onDelete:'{$this->constraints[$deleteConstraint]}')]\n";
                break;
            case 'o':
                $this->uses['OneToMany'] = "use App\\Kernel\\Connector\\Attributes\\OneToMany;\n";
                $this->uses['LazyBag'] = "use App\\Kernel\\Connector\\Datas\\LazyBag;\n";
                $target = $relation['foreign'];
                $newPropertyType = 'LazyBag';
                $fieldTarget = $relation['field'];
                $this->properties .= "    #[OneToMany(targetEntity: {$target}::class, mappedBy:'{$fieldTarget}')]\n";
                break;
            default:
                throw new ConsoleException();
        }
        return $newPropertyType;
    }
}
