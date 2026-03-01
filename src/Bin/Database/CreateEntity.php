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
    private bool $isNew = true;
    private array $types = [
        's' => 'string',
        'i' => 'int',
        'dt' => 'DateTimeImmutable',
        'd' => 'DateTime',
        'f' => 'float',
        'b' => 'bool',
        'a' => 'array',
        'r' => 'relation'
    ];
    private array $relations = [
        'm' => 'Many to One (this entity store field for relation)',
    ];
    private array $restrictions = [
        'c' => 'cascade',
        'r' => 'restrict',
        'a' => 'no action',
        'n' => 'set null'
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
        if ($isEntityExists || 'User' === $entityName) {
            $special = ConsoleHelper::makeSpecial('Erreur :', 'red', 'bold');
            echo "{$special} Entity {$entityName} already exists. Make change manually.";
            //Keep it
            $this->isNew = false;
            return;
        }
        $properties = [];
        $relations = [];
        $action = '';
        do {
            $propertyName = $this->askPropertyName();
            $propertyType = $this->askPropertyType();
            if ('r' === $propertyType) {
                $propertyRelation = $this->makeRelation($propertyName);
                $stored = true;
            } else {
                $yesNo = ConsoleHelper::askWhile('Is property stored in DB ? (y/n)', ['y', 'n']);
                $stored = $yesNo === 'y';
            }
            $yesNo = ConsoleHelper::askWhile('Is property nullable ? (y/n)', ['y', 'n']);
            $nullable = $yesNo === 'y';
            $displayNull = $nullable ? '' : 'not ';
            $displayStore = $stored ? '' : 'not ';
            $display = "Property {$propertyName}, type {$this->types[$propertyType]} {$displayNull}null {$displayStore}stored in DB\n";
            if ('r' === $propertyType) {
                $relationKey =  $propertyRelation['type'];
                $displayRelation = $this->relations[$relationKey];
                $displayForeignTable = $propertyRelation['foreign'];
                $displayForeignField = $propertyRelation['field'];
                $displayUpdateKey = $propertyRelation['update'];
                $displayDeleteKey = $propertyRelation['delete'];
                $displayUpdate = $this->restrictions[$displayUpdateKey];
                $displayDelete = $this->restrictions[$displayDeleteKey];
                $display = "Relation {$displayRelation} with {$displayForeignTable} on {$displayForeignField} with {$displayUpdate} on update and {$displayDelete} on delete constraints\n";
                $propertyRelation;
            }
            echo $display;

            $yesNo = ConsoleHelper::askWhile('Is this OK ? (y/n)', ['y', 'n']);
            $ok = $yesNo === 'y';
            if ($ok) {
                if ('r' === $propertyType) {
                    $relations[$propertyName] = $propertyRelation;
                }
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
            $display = "Property {$key}, type {$this->types[$type]} {$displayNull}null {$displayStore}stored in DB\n";
            echo $display;
        }
        $yesNo = ConsoleHelper::askWhile('Create files ? (y/n)', ['y', 'n']);
        $answer = $yesNo === 'y';
        if ($answer) {
            if ($this->isNew) {
                $this->createFiles($entityName, $properties, $relations);
            } else {
                $this->updateEntity($entityName, $properties, $relations);
            }
        }
    }

    private function makeRelation(string $name): array
    {
        $result = [];
        $question = "type of relation : \n";
        $autorized = [];
        foreach ($this->relations as $key => $value) {
            $question .= "{$key} : {$value}\n";
            $autorized[] = $key;
        }
        $reponseRelationType = ConsoleHelper::askWhile($question, $autorized);
        $result['type'] = $reponseRelationType;
        $question = "Name of related Entity : \n";
        do {
            $relation = ucfirst(ConsoleHelper::ask($question));
            $fullName = "App\\Entity\\" . $relation;
        } while (!ConsoleHelper::checkClassExistsAndOK($fullName, EntityInterface::class));
        $result['foreign'] = $relation;
        $question = 'Name of the field in foreign Entity';
        $foreignField = ConsoleHelper::ask($question);
        $result['field'] = $foreignField;
        if ("m" === $reponseRelationType) {
            $result['update'] = $this->makeConstraint("Constraints on Update ?\n");
            $result['delete'] = $this->makeConstraint("Constraints on Delete'\n");
        } else {
            $result['update'] = null;
            $result['delete'] = null;
        }
        return $result;
    }

    private function makeConstraint(string $question): string
    {
        foreach ($this->restrictions as $key => $value) {
            $question .= "{$key} : {$value}\n";
            $autorized[] = $key;
        }
        $reponseRelationType = ConsoleHelper::askWhile($question, $autorized);
        return $reponseRelationType;
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

    private function createFiles(string $name, array $propertiesArray, array $fieldRelations): void
    {
        $EntityCreator = new MakeEntityFile($this->appPath, $this->types, $this->relations, $this->restrictions);
        $entityCreate = $EntityCreator->createEntityFile($name, $propertiesArray, $fieldRelations);
        if (!$entityCreate) {
            $error = ConsoleHelper::makeSpecial('Error', 'red', 'bold');
            echo "{$error} : Entity not created. Aborting\n";
            return;
        }
        $ok = ConsoleHelper::makeSpecial('Entity create successfully...', 'green', 'reset');
        echo "$ok\n";
        echo "Create repository\n";
        $repositoryCreator = new MakeRepositoryFile($this->appPath);
        $repositoryCreate = $repositoryCreator->createRepositoryFile($name);
        if (!$repositoryCreate) {
            $error = ConsoleHelper::makeSpecial('Error', 'red', 'bold');
            echo "{$error} : Repository not created\n";
            return;
        }
        $ok = ConsoleHelper::makeSpecial('Repository create successfully...', 'green', 'reset');
        echo "$ok\n";
        //here if relations update the existing Entity whith OneToMany case(local fields in ManyToOne)
        $arrayEntitiesToChange = $this->makeAdapterNewForUpdate($name, $propertiesArray, $fieldRelations);
        foreach ($arrayEntitiesToChange as $foreignEntity => $informations) {
            $properties = $informations['properties'];
            $relation = $informations['relations'];
            $fileSaved = $this->updateEntity($foreignEntity, $properties, $relation);
            if ($fileSaved) {
                $ok = ConsoleHelper::makeSpecial("Entity {$foreignEntity} updated successfully...", 'green', 'reset');
                echo "$ok\n";
            } else {
                $error = ConsoleHelper::makeSpecial('Error', 'red', 'bold');
                echo "{$error} : Entity {$foreignEntity} was not updated\n";
            }
        }
        return;
    }

    private function makeAdapterNewForUpdate(string $name, array $propertiesArray, array $fieldRelations): array
    {
        /*Structure du tableau : 
        [
            'entityName' =>[
                'properties' =>[
                    'realname of property' =>[
                        'type' => string 1 car,
                        'stored' => bool,
                        'nullable' => bool
                    ],
                    'realname of property' =>[
                        'type' => string 1 car,
                        'stored' => bool,
                        'nullable' => bool
                    ],
                ],
                '$relations' =>[
                    'realName of Property' =>[
                        'type' => string 1c,
                        'foreign' => string Class foreign,
                        'field' => string foreign field
                        'update' => string 1c,
                        'delete' => string 1c
                    ],
                     'realName of Property' =>[
                        'type' => string 1c,
                        'foreign' => string Class foreign,
                        'field' => string foreign field
                        'update' => string 1c,
                        'delete' => string 1c
                    ],
                ]
            ],
        ]
        */
        $returnArray = [];
        //For OneToMany
        foreach ($fieldRelations as $fieldname => $relations) {
            $entityName = $relations['foreign'];
            $propertyName = $relations['field'];
            $relationType = $relations['type'];
            if ('m' === $relationType) {
                $foreignRelation = 'o';
                $delete = $relations['delete'] ?? null;
                $update = $relations['update'] ?? null;
                $typeField = 'LazyBag';
            }
            if ('o' === $relationType) {
                $foreignRelation = 'm';
                $delete = '';
                $update = '';
                $typeField = "{$name}Entity";
            }
            $Newproperties = [
                'type' => $typeField,
                'stored' => true,
                'nullable' => false
            ];
            $newRelations = [
                'type' => $foreignRelation,
                'foreign' => "{$name}Entity",
                'field' =>  $fieldname,
                'update' => $update,
                'delete' => $delete

            ];
            $returnArray[$entityName]['properties'][$propertyName] = $Newproperties;
            $returnArray[$entityName]['relations'][$propertyName] = $newRelations;
        }
        return $returnArray;
    }

    private function  checkEntityExists($name): bool
    {
        $entityName = "{$name}Entity";
        $path = $this->appPath . 'src' . DIRECTORY_SEPARATOR . 'Entity' . DIRECTORY_SEPARATOR;
        $filename = "{$path}{$entityName}.php";
        return file_exists($filename);
    }

    private function updateEntity(string $name, array $propertiesArray, array $fieldRelations): bool
    {
        $fileSaved = false;
        $EntityPath = GetEnvDatas::getAppPath() . 'src' . DIRECTORY_SEPARATOR . 'Entity' . DIRECTORY_SEPARATOR;
        $filePath = $EntityPath . $name . '.php';
        try {
            foreach ($propertiesArray as $propertyName => $infos) {
                $useStatements = [];
                $attributes = [];
                $propertyType = $infos['type'];
                if ($infos['nullable']) {
                    $useStatements[] = 'App\\Kernel\\Connector\\Attributes\\Nullable';
                    $attributes[] = '#[Nullable]';
                }
                if (!$infos['stored']) {
                    $useStatements[] = 'App\\Kernel\\Connector\\Attributes\\NoStored';
                    $attributes[] = '#[NotStored]';
                }
                //Do for relations
                if (!empty($fieldRelations)) {
                    $relationProperty = $fieldRelations[$propertyName] ?? [];
                    if (!empty($relationProperty)) {
                        $relationArgs = $this->retrieveRelation($propertyName, $relationProperty);
                        $attributes[] = $relationArgs['argument'];
                        foreach($relationArgs['use'] as $use){
                            $useStatements[] = $use;
                        }
                    }
                }
                return EntityModifier::addProperty($filePath, $propertyName, $propertyType, $useStatements, $attributes);
            }
        } catch (Exception $e) {
            $fileSaved = false;
        }
        return $fileSaved;
    }

    private function retrieveRelation(string $propertyName, array $relation): array
    {
        $remoteClass = $relation['foreign'];
        $remoteField = $relation['field'];
        switch ($relation['type']) {
            case 'o':
                $use [] = "App\Kernel\Connector\Attributes\OneToMany";
                $use [] = "App\Kernel\Connector\Datas\LazyBag";
                $argument = "#[OneToMany(targetEntity: {$remoteClass}::class, mappedBy: '{$remoteField}')]";
                break;
            case 'm':
                $use = "App\Kernel\Connector\Attributes\ManyToOne";
                $argument = "#[ManyToOne(targetEntity: {$remoteClass}::class, inversedBy: '{$remoteField}', onUpdate: '{}', onDelete: '{}')]";
                break;
        }
        return [
            'argument' => $argument,
            'use' => $use
        ];
    }
}
