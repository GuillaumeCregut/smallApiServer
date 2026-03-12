<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Bin\Database;

use Exception;
use App\Bin\ConsoleHelper;
use App\Kernel\GetEnvDatas;
use App\Bin\ConsoleException;
use App\Kernel\Connector\Interfaces\EntityInterface;


class CreateEntity
{
    private string $appPath;
    private string $EntityPath;
    private bool $isNew = true;
    private array $types = [
        's' => 'string',
        'i' => 'int',
        'dt' => 'DateTimeImmutable',
        'd' => 'DateTime',
        'f' => 'float',
        'b' => 'bool',
        'a' => 'array',
        'r' => 'relation',
        'fl' =>'FileUpload'
    ];
    private array $relations = [
        'm' => 'Many to One (this entity store field for relation)',
        'o' => 'One to Many (this entity does not store field for relation)',
    ];
    private array $restrictions = [
        'c' => 'cascade',
        'r' => 'restrict',
        'a' => 'no action',
        'n' => 'set null'
    ];
    private array $entityToStore = [];
    private array $foreignEntities = [];
    private string $entityClassName = '';

    public function __construct()
    {
        $this->appPath = GetEnvDatas::getAppPath();
        $this->EntityPath = $this->appPath . 'src' . DIRECTORY_SEPARATOR . 'Entity' . DIRECTORY_SEPARATOR;
    }

    public function execute(string $arg): void
    {
        $arg = ucfirst($arg);
        $this->entityToStore['properties'] = [];
        $this->entityToStore['relations'] = [];
        $action = '';
        $entityExists = $this->checkForEntityName($arg);
        if ($entityExists || 'User' === $this->entityClassName) {
            $special = ConsoleHelper::makeSpecial('Warning :', 'red', 'bold');
            echo "{$special} Entity {$this->entityClassName} already exists. Add new fields.\n";
            $this->isNew = false;
        }

        do {
            $this->makeProperty();
            $action = ConsoleHelper::ask('Add a new Property ? (n for stop) ');
        } while ('n' !== $action);

        $this->displayEntitySummary();

        $question = 'Create files ? (y/n)';
        $saveFile = $this->askYesNo($question);
        if ($saveFile) {
            $this->saveFile();
        }
    }

    private function saveFile(): void
    {
        if ($this->isNew) {
            $this->createFile();
        } else {
            $this->updateEntity();
        }
    }

    /**
     * Save modification made in existing entity
     *
     * @return void
     */
    private function updateEntity(): void
    {
        $properties = $this->entityToStore['properties'];
        $relations = $this->entityToStore['relations'];
        foreach ($properties as $name => $values) {
            $relationType = null;
            $relationName = null;
            $relation = [];
            if (isset($relations[$name])) {
                $relation = $relations[$name];
                $relationType = $relation['type'];
                $relationName = $relation['foreign'];
            }
            $formattedValues = $this->formatUpdate($values, $relation);
            $formattedType = $formattedValues['type'];
            if ('o' === $relationType) {
                $formattedType = 'LazyBag';
            }
            if('m' === $relationType) {
                $formattedType = $relation['foreign'];
            }
            $saved = $this->writeFile($this->entityClassName, $name,  $formattedType, $formattedValues['uses'], $formattedValues['attributes'], $relationType,  $relationName);
            if ($saved) {
                $ok = ConsoleHelper::makeSpecial("Property {$name} create successfully...", 'green', 'reset');
                echo "$ok\n";
            } else {
                $error = ConsoleHelper::makeSpecial("Error creating property {$name}", 'red', 'reset');
                echo "$error\n";
            }
        }
        //Save foreign Entities 
        if (!empty($this->foreignEntities)) {
            foreach ($this->foreignEntities as $entityName => $entityInformations) {
                $saveEntity = $this->saveForeignEntity($entityName, $entityInformations);
                if ($saveEntity) {
                    $ok = ConsoleHelper::makeSpecial("Entity {$entityName} modified successfully...", 'green', 'reset');
                    echo "$ok\n";
                } else {
                    $error = ConsoleHelper::makeSpecial("Error updating entity {$entityName}", 'red', 'reset');
                    echo "$error\n";
                }
            }
        }
    }

    /**
     * Format datas (relation, propertie) for adding in an existant Entity
     *
     * @param array $property
     * @param array $relation
     * @return array
     */
    private function formatUpdate(array $property, array $relation): array
    {
        $returnArray = [
            'type' => '',
            'uses' => [],
            'attributes' => []
        ];
        $uses = [];
        $attributes = [];
        $propertyType = $property['type'];
        if ('o' === $propertyType) {
            if (empty($relation)) {
                throw new Exception('Error with entity in formatUpdate. No relation found');
            }
            if ('o' === $relation['type']) {
                $type = 'LazyBag';
            } else {
                $type = $relation['foreign'];
            }
        } else {
            $type = $this->types[$propertyType];
        }
        if ($property['nullable']) {
            $uses[] = 'App\\Kernel\\Connector\\Attributes\\Nullable';
            $attributes[] = '#[Nullable]';
        }

        if (!$property['stored']) {
            $uses[] = 'App\\Kernel\\Connector\\Attributes\\NotStored';
            $attributes[] = '#[NotStored]';
        }
        //Cas des relations
        if (!empty($relation)) {
            $relationType = $relation['type'];
            switch ($relationType) {
                case 'm':
                    $uses[] = 'App\\Kernel\\Connector\\Attributes\\ManyToOne';
                    $target = $relation['foreign'];
                    $field = $relation['field'];
                    $updateConstraintKey = $relation['update'];
                    $deleteConstraintKey = $relation['delete'];
                    $updateConstraint = $this->restrictions[$updateConstraintKey];
                    $deleteConstraint = $this->restrictions[$deleteConstraintKey];
                    $uses[] = "App\\Entity\\{$target}";
                    $attributes[] = "#[ManyToOne(targetEntity: {$target}::class, inversedBy: '{$field}', onUpdate: '{$updateConstraint}', onDelete: '$deleteConstraint')]";
                    break;
                case 'o':
                    $uses[] = 'App\\Kernel\\Connector\\Attributes\\OneToMany';
                    $uses[] = 'App\\Kernel\\Connector\\Datas\\LazyBag';
                    $target = $relation['foreign'];
                    $field = $relation['field'];
                    $attributes[] = "#[OneToMany(targetEntity: {$target}::class, mappedBy: '{$field}')]";
                    break;
            }
        }
        $returnArray['type'] = $type;
        $returnArray['uses'] = $uses;
        $returnArray['attributes'] = $attributes;
        return $returnArray;
    }

    /**
     * Add modification to an existing entity
     *
     * @param string $entityName
     * @param string $propertyName
     * @param string $propertyType
     * @param array $useStatements
     * @param array $attributes
     * @return boolean
     */
    private function writeFile(string $entityName, string $propertyName, string $propertyType, array $useStatements, array $attributes, ?string $relationType = null, ?string $relationName = null): bool
    {
        $filePath = $this->EntityPath . $entityName . '.php';
        return EntityModifier::addProperty($filePath, $propertyName, $propertyType, $useStatements, $attributes, $relationType, $relationName);
    }

    /**
     * Save a new Entity, Create Repository, save relations Entities
     *
     * @return void
     */
    private function createFile(): void
    {
        $properties = $this->entityToStore['properties'];
        $relations = $this->entityToStore['relations'];
        $EntityCreator = new MakeEntityFile($this->appPath, $this->types, $this->restrictions);
        $entityCreate = $EntityCreator->createEntityFile($this->entityClassName,  $properties, $relations);
        if (!$entityCreate) {
            $error = ConsoleHelper::makeSpecial('Error', 'red', 'bold');
            echo "{$error} : Entity not created. Aborting\n";
            return;
        }
        $ok = ConsoleHelper::makeSpecial('Entity create successfully...', 'green', 'reset');
        echo "$ok\n";
        echo "Create repository\n";
        $repositoryCreator = new MakeRepositoryFile($this->appPath);
        $repositoryCreate = $repositoryCreator->createRepositoryFile($this->entityClassName);
        if (!$repositoryCreate) {
            $error = ConsoleHelper::makeSpecial('Error', 'red', 'bold');
            echo "{$error} : Repository not created\n";
            return;
        }
        $ok = ConsoleHelper::makeSpecial('Repository create successfully...', 'green', 'reset');
        echo "$ok\n";
        if (!empty($this->foreignEntities)) {
            echo "Update foreign entities\n";
            foreach ($this->foreignEntities as $entityName => $entityInformations) {
                $saveEntity = $this->saveForeignEntity($entityName, $entityInformations);
                if ($saveEntity) {
                    $ok = ConsoleHelper::makeSpecial("Entity {$entityName} modified successfully...", 'green', 'reset');
                    echo "$ok\n";
                } else {
                    $error = ConsoleHelper::makeSpecial("Error updating entity {$entityName}", 'red', 'reset');
                    echo "$error\n";
                }
            }
        }
    }

    /**
     * Save foreign Entity
     *
     * @param string $entityName
     * @param array $properties
     * @return boolean
     */
    private function saveForeignEntity(string $entityName, array $properties): bool
    {
        $success = true;
        foreach ($properties as $property => $informations) {
            $relationName = null;
            $relationType = null;
            $relations = $informations['relations'];
            $properties = $informations['properties'];
            if (!empty($relations)) {
                $relationName = $relations['foreign'];
                $relationType = $relations['type'];
            }
            $formattedValues = $this->formatUpdate($properties, $relations);
            $saved = $this->writeFile($entityName, $property, $formattedValues['type'], $formattedValues['uses'], $formattedValues['attributes'], $relationType, $relationName);
            if ($saved) {
                $ok = ConsoleHelper::makeSpecial("Property {$property} in {$entityName} create successfully...", 'green', 'reset');
                echo "$ok\n";
            } else {
                $error = ConsoleHelper::makeSpecial("Error creating property {$property} in {$entityName}", 'red', 'reset');
                echo "$error\n";
                $success = false;
            }
        }
        return $success;
    }

    /**
     * Display summary of entity
     *
     * @return void
     */
    private function displayEntitySummary(): void
    {
        $properties = $this->entityToStore['properties'];
        echo "Those infomations will be save into {$this->entityClassName} : \n";
        foreach ($properties as $key => $values) {
            $displayNull = $values['nullable'] ? '' : 'not ';
            $displayStore = $values['stored'] ? '' : 'not ';
            $type = $values['type'];
            $display = "Property {$key}, type {$this->types[$type]} {$displayNull}null {$displayStore}stored in DB\n";
            echo $display;
        }
    }

    /**
     * Ask user for property informations.
     *
     * @return void
     */
    private function makeProperty(): void
    {
        $propertyName = null;
        $property = [];
        $relation = [];
        $storeProperty = true;
        $nullProperty = false;
        do {
            $propertyName = $this->askPropertyName();
            $propertyExists = $this->checkPropertyExists($propertyName, $this->entityClassName, $this->isNew);
            if ($propertyExists) {
                $error = ConsoleHelper::makeSpecial('Error', 'red', 'bold');
                echo "{$error} property {$propertyName} already exists in class\n";
            }
        } while ($propertyExists);
        $propertyType = $this->askPropertyType();
        if ('r' === $propertyType) {
            $relation = $this->makeRelation();
        } else {
            $storeProperty = $this->askYesNo('Is property stored in DB ? (y/n)');
        }
        if ($storeProperty) {
            $nullProperty = $this->askYesNo('Is property nullable ? (y/n)');
        }

        $property = [
            'stored' => $storeProperty,
            'type' => $propertyType,
            'nullable' => $nullProperty,
            'foreign' => null
        ];

        $this->displayProperty($propertyName, $property, $relation);
        $saveProperty = $this->askYesNo('Is this OK ? (y/n)');
        if ($saveProperty) {
            $this->entityToStore['properties'][$propertyName] = $property;
            if (!empty($relation)) {
                $this->entityToStore['relations'][$propertyName] = $relation;
                $this->makeForeignUpdate($propertyName, $property, $relation);
            }
        } else {
            echo "Property not saved.\n";
        }
    }

    /**
     * If relation is set, calculate fields to add in foreign classes
     *
     * @param string $property
     * @param array $values
     * @param array $relation
     * @return void
     */
    private function makeForeignUpdate(string $property, array $values, array $relation): void
    {
        $foreignEntityName = $relation['foreign'];
        $foreignPropertyName = $relation['field'];
        $foreignProperty = [
            'type' => '',
            'stored' => true,
            'nullable' => false
        ];
        $foreignRelation = [
            'type' => '',
            'foreign' => '',
            'field' => '',
            'update' => '',
            'delete' => ''
        ];
        $typeRelation = $relation['type'];
        switch ($typeRelation) {
            case 'm':
                $foreignRelation['type'] = 'o';
                $foreignRelation['update'] = null;
                $foreignRelation['delete'] = null;
                $foreignProperty['foreign'] = $this->entityClassName;
                $foreignProperty['nullable'] = $values['nullable'];
                break;
            case 'o':
                $foreignRelation['type'] = 'm';
                $foreignRelation['update'] = $relation['update'];
                $foreignRelation['delete'] = $relation['delete'];
                $foreignProperty['foreign'] = 'LazyBag';
        }
        $foreignRelation['foreign'] = $this->entityClassName;
        $foreignRelation['field'] = $property;
        $foreignProperty['type'] = 'o';
        $this->foreignEntities[$foreignEntityName][$foreignPropertyName]['properties'] = $foreignProperty;
        $this->foreignEntities[$foreignEntityName][$foreignPropertyName]['relations'] = $foreignRelation;
    }

    /**
     * Ask question for relation, create relation Array
     *
     * @return array
     */
    private function makeRelation(): array
    {
        $returnArray = [];
        $question = "type of relation : \n";
        $autorized = [];
        foreach ($this->relations as $key => $value) {
            $question .= "{$key} : {$value}\n";
            $autorized[] = $key;
        }
        $relationType = ConsoleHelper::askWhile($question, $autorized);
        $returnArray['type'] = $relationType;

        //related entity
        $question = "Name of related Entity : \n";
        $foreignExists = false;
        do {
            $relation = ucfirst(ConsoleHelper::ask($question));
            if (!str_ends_with($relation, 'Entity')) {
                $relation = "{$relation}Entity";
            }
            $fullName = "App\\Entity\\" . $relation;
            $foreignExists = ConsoleHelper::checkClassExistsAndOK($fullName, EntityInterface::class);
            if (!$foreignExists) {
                $error = ConsoleHelper::makeSpecial('Error', 'red', 'bold');
                echo "{$error} : Entity {$relation} does not exist\n";
            }
        } while (!$foreignExists);
        $returnArray['foreign'] = $relation;

        //field in related entity
        $question = 'Name of the field in foreign Entity';
        $foreignPropertyExists = false;
        do {
            $foreignField = ConsoleHelper::ask($question);
            $foreignPropertyExists = $this->checkPropertyExists($foreignField, $relation, false);
            if ($foreignPropertyExists) {
                $error = ConsoleHelper::makeSpecial('Error', 'red', 'bold');
                echo "{$error} : Property {$foreignField} exists in {$relation}\n";
            }
        } while ($foreignPropertyExists);
        $returnArray['field'] =  $foreignField;
        $returnArray['update'] = $this->makeConstraint("Constraints on Update ?\n");
        $returnArray['delete'] = $this->makeConstraint("Constraints on Delete'\n");
        return $returnArray;
    }

    /**
     * Get informations for constraints
     *
     * @param string $question
     * @return boolean
     */
    private function makeConstraint(string $question): string
    {
        foreach ($this->restrictions as $key => $value) {
            $question .= "{$key} : {$value}\n";
            $autorized[] = $key;
        }
        $reponseRelationType = ConsoleHelper::askWhile($question, $autorized);
        return $reponseRelationType;
    }

    /**
     * Display summary of property
     *
     * @param string $propertyName
     * @param array $values
     * @param array $relations
     * @return void
     */
    private function displayProperty(string $propertyName, array $values, array $relations): void
    {
        $displayNull = $values['nullable'] ? '' : 'not ';
        $displayStore = $values['stored'] ? '' : 'not ';
        $propertyType = $values['type'];
        $display = "Property {$propertyName}, type {$this->types[$propertyType]} {$displayNull}null {$displayStore}stored in DB";
        echo "{$display}\n";
    }

    private function askYesNo(string $question): bool
    {
        $yesNo = ConsoleHelper::askWhile($question, ['y', 'n']);
        return $yesNo === 'y';
    }

    /**
     * Ask for property type, return key of property
     *
     * @return string
     */
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


    /**
     * Check if entity already exists, set name in global class
     * 
     * @param string $name
     * 
     */
    private function checkForEntityName(string $name): bool
    {
        if (!(preg_match('/^[a-zA-Z]+$/', $name) === 1)) {
            throw new ConsoleException("{$name} is not a valid name");
        }
        $entityName = ucfirst($name);
        $this->entityClassName = $entityName;
        if (!str_ends_with($name, 'Entity')) {
            $entityName = "{$name}Entity";
        }
        $this->entityClassName = $entityName;
        $filename = "{$this->EntityPath}{$entityName}.php";
        return file_exists($filename);
    }

    /**
     * Check if property exist in current class or in an other class
     *
     * @param string $property
     * @param string $class
     * @return boolean
     */
    private function checkPropertyExists(string $property, string $class, bool $isNew): bool
    {
        if ($isNew) {
            $properties = $this->entityToStore['properties'];
            return array_key_exists($property, $properties);
        }
        $fullName = "App\\Entity\\" . $class;
        return property_exists($fullName, $property);
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
}
