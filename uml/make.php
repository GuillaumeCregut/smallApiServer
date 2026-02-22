<?php

use App\Kernel\GetEnvDatas;

$filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR;
require_once $filePath . 'Autoload.php';

App\Vendor\Autoload::register();

function saveTofile(array $data, ?bool $explode = false)
{
    $now = date('Ymd');
    $path = GetEnvDatas::getAppPath() . 'uml' . DIRECTORY_SEPARATOR;
    if (!$explode) {
        $filename = "uml{$now}.json";
        $filePath = $path . $filename;
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($filePath, $json);
    } else {
        foreach ($data as $key => $list) {
            $newFilePath = $path . $key . DIRECTORY_SEPARATOR;
            if (!is_dir($newFilePath)) {
                mkdir($newFilePath);
            }
            foreach ($list as $class => $details) {
                $filename = str_replace('\\', '-', $class) . '.json';
                $fullName = $newFilePath . $filename;
                $json = json_encode($details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                file_put_contents($fullName, $json);
            }
        }
    }
}

function browse(): array
{
    $path = GetEnvDatas::getAppPath() . "src" . DIRECTORY_SEPARATOR;
    $fileArray = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $ubPath => $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $relativeName = str_replace($path, '', $file->getRealPath());
            $classname = convertToClassName($relativeName);
            if (isClass($classname)) {
                if (returnAbstractClass($classname)) {
                    $fileArray['abstract'][] = convertToClassName($relativeName);
                } else {

                    $fileArray['implement'][] = convertToClassName($relativeName);
                }
            }
        }
    }
    return $fileArray;
}

function convertToClassName(string $file): string
{
    $className = preg_replace('/\.php$/', '', $file);
    $relativeClassName = str_replace('/', '\\', $className);
    $nameSpace = "App\\{$relativeClassName}";
    return $nameSpace;
}

function isClass(string $classname): bool
{
    return class_exists($classname);
}

function returnAbstractClass(string $classname): bool
{
    $reflection = new ReflectionClass($classname);
    return $reflection->isAbstract();
}


function getClassDetails(string $classname): array
{
    $result = [
        'privateProperty'     => [],
        'publicProperty'      => [],
        'protectedProperty'   => [],
        'privateFunction'  => [],
        'publicFunction'   => [],
        'protectedFunction' => [],
    ];
    $reflection = new ReflectionClass($classname);
    foreach ($reflection->getProperties() as $property) {
        $info = [
            'name' => $property->getName(),
            'type' => resolveType($property->getType()),
        ];

        if ($property->isPrivate()) {
            $result['privateProperty'][]   = $info;
        } elseif ($property->isPublic()) {
            $result['publicProperty'][]    = $info;
        } elseif ($property->isProtected()) {
            $result['protectedProperty'][] = $info;
        }
    }

    // Méthodes
    foreach ($reflection->getMethods() as $method) {
        $params = [];
        foreach ($method->getParameters() as $param) {
            $params[] = [
                'name' => $param->getName(),
                'type' => resolveType($param->getType()),
            ];
        }

        $info = [
            'name'       => $method->getName(),
            'returnType' => resolveType($method->getReturnType()),
            'params'     => $params,
        ];

        if ($method->isPrivate()) {
            $result['privateFunction'][]   = $info;
        } elseif ($method->isPublic()) {
            $result['publicFunction'][]    = $info;
        } elseif ($method->isProtected()) {
            $result['protectedFunction'][] = $info;
        }
    }
    return $result;
}
function resolveType(\ReflectionType|null $type): string
{
    if ($type === null) {
        return 'mixed';
    }
    if ($type instanceof \ReflectionNamedType) {
        return $type->getName();
    }
    if ($type instanceof \ReflectionUnionType) {
        return implode('|', array_map(resolveType(...), $type->getTypes()));
    }
    if ($type instanceof \ReflectionIntersectionType) {
        return implode('&', array_map(resolveType(...), $type->getTypes()));
    }
    return 'mixed';
}

function getAllDetails(array $listClasses): array
{
    $detailedClasses = [];
    foreach ($listClasses as $key => $list) {
        foreach ($list as $class) {
            $details = getClassDetails($class);
            $detailedClasses[$key][$class] = $details;
        }
    }
    return  $detailedClasses;
}

$tempArray = browse();
$resultArray = getAllDetails($tempArray);
saveTofile($resultArray, true);
