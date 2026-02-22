<?php

use App\Kernel\GetEnvDatas;

$filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR;
require_once $filePath . 'Autoload.php';

App\Vendor\Autoload::register();

function formatClassName(string $classPath): string
{
    // Convert App-Kernel-Logger.json to App\Kernel\Logger
    $class = str_replace('-', '\\', $classPath);
    return $class;
}

function generateUMLDocumentation(string $className, array $classDetails): string
{
    $markdown = "# UML Diagram: $className\n\n";
    
    // Class header
    $markdown .= "## Class Definition\n\n";
    $markdown .= "```\n";
    $markdown .= "class $className\n";
    $markdown .= "```\n\n";
    
    // Properties section
    if (!empty($classDetails['publicProperty']) || !empty($classDetails['protectedProperty']) || !empty($classDetails['privateProperty'])) {
        $markdown .= "## Properties\n\n";
        
        if (!empty($classDetails['publicProperty'])) {
            $markdown .= "### Public Properties\n\n";
            $markdown .= "| Name | Type |\n";
            $markdown .= "|------|------|\n";
            foreach ($classDetails['publicProperty'] as $prop) {
                $markdown .= "| `" . $prop['name'] . "` | `" . $prop['type'] . "` |\n";
            }
            $markdown .= "\n";
        }
        
        if (!empty($classDetails['protectedProperty'])) {
            $markdown .= "### Protected Properties\n\n";
            $markdown .= "| Name | Type |\n";
            $markdown .= "|------|------|\n";
            foreach ($classDetails['protectedProperty'] as $prop) {
                $markdown .= "| `" . $prop['name'] . "` | `" . $prop['type'] . "` |\n";
            }
            $markdown .= "\n";
        }
        
        if (!empty($classDetails['privateProperty'])) {
            $markdown .= "### Private Properties\n\n";
            $markdown .= "| Name | Type |\n";
            $markdown .= "|------|------|\n";
            foreach ($classDetails['privateProperty'] as $prop) {
                $markdown .= "| `" . $prop['name'] . "` | `" . $prop['type'] . "` |\n";
            }
            $markdown .= "\n";
        }
    }
    
    // Methods section
    if (!empty($classDetails['publicFunction']) || !empty($classDetails['protectedFunction']) || !empty($classDetails['privateFunction'])) {
        $markdown .= "## Methods\n\n";
        
        if (!empty($classDetails['publicFunction'])) {
            $markdown .= "### Public Methods\n\n";
            foreach ($classDetails['publicFunction'] as $method) {
                $params = implode(', ', array_map(function($p) {
                    return '$' . $p['name'] . ': ' . $p['type'];
                }, $method['params']));
                
                $markdown .= "#### `" . $method['name'] . "($params): " . $method['returnType'] . "`\n\n";
            }
        }
        
        if (!empty($classDetails['protectedFunction'])) {
            $markdown .= "### Protected Methods\n\n";
            foreach ($classDetails['protectedFunction'] as $method) {
                $params = implode(', ', array_map(function($p) {
                    return '$' . $p['name'] . ': ' . $p['type'];
                }, $method['params']));
                
                $markdown .= "#### `" . $method['name'] . "($params): " . $method['returnType'] . "`\n\n";
            }
        }
        
        if (!empty($classDetails['privateFunction'])) {
            $markdown .= "### Private Methods\n\n";
            foreach ($classDetails['privateFunction'] as $method) {
                $params = implode(', ', array_map(function($p) {
                    return '$' . $p['name'] . ': ' . $p['type'];
                }, $method['params']));
                
                $markdown .= "#### `" . $method['name'] . "($params): " . $method['returnType'] . "`\n\n";
            }
        }
    }
    
    return $markdown;
}

function generatePlantUML(string $className, array $classDetails, bool $isAbstract): string
{
    $plantuml = "@startuml " . str_replace('\\', '_', $className) . "\n";
    $plantuml .= "!theme plain\n";
    $plantuml .= "skinparam classBackgroundColor #F0F0F0\n";
    $plantuml .= "skinparam classBorderColor #333333\n\n";
    
    // Class declaration
    if ($isAbstract) {
        $plantuml .= "abstract class " . basename($className) . " {\n";
    } else {
        $plantuml .= "class " . basename($className) . " {\n";
    }
    
    // Properties
    if (!empty($classDetails['publicProperty'])) {
        foreach ($classDetails['publicProperty'] as $prop) {
            $plantuml .= "  + {field} " . $prop['name'] . ": " . $prop['type'] . "\n";
        }
    }
    
    if (!empty($classDetails['protectedProperty'])) {
        foreach ($classDetails['protectedProperty'] as $prop) {
            $plantuml .= "  # {field} " . $prop['name'] . ": " . $prop['type'] . "\n";
        }
    }
    
    if (!empty($classDetails['privateProperty'])) {
        foreach ($classDetails['privateProperty'] as $prop) {
            $plantuml .= "  - {field} " . $prop['name'] . ": " . $prop['type'] . "\n";
        }
    }
    
    // Methods
    if (!empty($classDetails['publicFunction'])) {
        foreach ($classDetails['publicFunction'] as $method) {
            $params = implode(', ', array_map(function($p) {
                return $p['name'] . ': ' . $p['type'];
            }, $method['params']));
            $plantuml .= "  + " . $method['name'] . "(" . $params . "): " . $method['returnType'] . "\n";
        }
    }
    
    if (!empty($classDetails['protectedFunction'])) {
        foreach ($classDetails['protectedFunction'] as $method) {
            $params = implode(', ', array_map(function($p) {
                return $p['name'] . ': ' . $p['type'];
            }, $method['params']));
            $plantuml .= "  # " . $method['name'] . "(" . $params . "): " . $method['returnType'] . "\n";
        }
    }
    
    if (!empty($classDetails['privateFunction'])) {
        foreach ($classDetails['privateFunction'] as $method) {
            $params = implode(', ', array_map(function($p) {
                return $p['name'] . ': ' . $p['type'];
            }, $method['params']));
            $plantuml .= "  - " . $method['name'] . "(" . $params . "): " . $method['returnType'] . "\n";
        }
    }
    
    $plantuml .= "}\n\n";
    $plantuml .= "@enduml\n";
    
    return $plantuml;
}

function processClassFile(string $filePath, string $fileName, bool $isAbstract): bool
{
    $json = file_get_contents($filePath);
    $classDetails = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Error reading $fileName: " . json_last_error_msg() . "\n";
        return false;
    }
    
    // Extract class name from filename (App-Kernel-Logger.json -> App\Kernel\Logger)
    $classPath = str_replace('.json', '', $fileName);
    $className = formatClassName($classPath);
    
    // Create markdown documentation
    $markdown = generateUMLDocumentation($className, $classDetails);
    $plantUML = generatePlantUML($className, $classDetails, $isAbstract);
    
    // Combined documentation
    $fullDoc = "# $className\n\n";
    $fullDoc .= "## PlantUML Diagram\n\n";
    $fullDoc .= "```plantuml\n";
    $fullDoc .= $plantUML;
    $fullDoc .= "```\n\n";
    $fullDoc .= $markdown;
    
    // Save to file
    $docsPath = GetEnvDatas::getAppPath() . 'uml' . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR;
    if (!is_dir($docsPath)) {
        mkdir($docsPath, 0755, true);
    }
    
    $outputFile = $docsPath . str_replace('\\', '-', $className) . '.md';
    file_put_contents($outputFile, $fullDoc);
    
    echo "✓ Generated: " . basename($outputFile) . "\n";
    return true;
}

// Process abstract classes
$abstractPath = GetEnvDatas::getAppPath() . 'uml' . DIRECTORY_SEPARATOR . 'abstract' . DIRECTORY_SEPARATOR;
echo "Processing Abstract Classes...\n";
$abstractDir = scandir($abstractPath);
foreach ($abstractDir as $file) {
    if ($file !== '.' && $file !== '..' && strpos($file, '.json') !== false) {
        processClassFile($abstractPath . $file, $file, true);
    }
}

echo "\nProcessing Concrete Classes...\n";
// Process concrete classes
$implementPath = GetEnvDatas::getAppPath() . 'uml' . DIRECTORY_SEPARATOR . 'implement' . DIRECTORY_SEPARATOR;
$implementDir = scandir($implementPath);
foreach ($implementDir as $file) {
    if ($file !== '.' && $file !== '..' && strpos($file, '.json') !== false) {
        processClassFile($implementPath . $file, $file, false);
    }
}

echo "\n✅ UML documentation generated successfully!\n";
echo "📁 Output location: " . GetEnvDatas::getAppPath() . 'uml/docs/' . "\n";
