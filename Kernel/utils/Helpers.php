<?php

declare(strict_types=1);

define('APP_DIR',dirname(__DIR__,2) . DIRECTORY_SEPARATOR);
/*
const
APP_DIR : the root directory of the application

functions :
dump : display any value in HTML format
dd : display any value in HTML format and stop the execution
ddjson : display any value in JSON format and stop the execution
*/

if (!function_exists('dump')) {
    /**
     * Affiche le contenu d'une ou plusieurs variables de manière lisible
     * 
     * @param mixed ...$vars Variables à afficher
     * @return void
     */
    function dump(...$vars): void
    {
        echo '<pre style="background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; margin: 10px 0; font-family: Consolas, monospace; font-size: 14px; line-height: 1.5; overflow-x: auto;">';
        
        foreach ($vars as $var) {
            var_dump($var);
        }
        
        echo '</pre>';
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and Die - Affiche et arrête l'exécution
     * 
     * @param mixed ...$vars Variables à afficher
     * @return never
     */
    function dd(...$vars): never
    {
        dump(...$vars);
        
        // Affiche la trace d'appel
        echo '<pre style="background: #2d2d2d; color: #888; padding: 10px; border-radius: 5px; margin: 10px 0; font-family: Consolas, monospace; font-size: 12px;">';
        echo '<strong>Called from:</strong>' . "\n";
        
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        if (isset($trace[0])) {
            echo $trace[0]['file'] ?? 'unknown';
            echo ':' . ($trace[0]['line'] ?? '?');
        }
        
        echo '</pre>';
        
        exit(1);
    }
}

if (!function_exists('ddjson')) {
      function ddjson(...$vars): void
    {   
        headers_sent() || header('Content-Type: application/json; charset=utf-8');
        foreach ($vars as $var) {
            // Convertir les objets en tableau pour le JSON
            $serializable = convert_to_serializable($var);
            echo json_encode($serializable, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            echo "\n";
        }
        die();
    }
}

if (!function_exists('convert_to_serializable')) {
    /**
     * Convertit récursivement n'importe quelle valeur en format sérialisable
     * 
     * @param mixed $value Valeur à convertir
     * @param int $depth Profondeur actuelle (pour éviter la récursion infinie)
     * @param array $visited Objets déjà visités (pour éviter les références circulaires)
     * @return mixed
     */
    function convert_to_serializable(mixed $value, int $depth = 0, array &$visited = []): mixed
    {
        // Limite de profondeur pour éviter la récursion infinie
        if ($depth > 10) {
            return '[Max depth reached]';
        }
        
        // Valeurs scalaires : retourner tel quel
        if (is_scalar($value) || is_null($value)) {
            return $value;
        }
        
        // Ressources
        if (is_resource($value)) {
            return '[Resource: ' . get_resource_type($value) . ']';
        }
        
        // Tableaux
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = convert_to_serializable($item, $depth + 1, $visited);
            }
            return $result;
        }
        
        // Objets
        if (is_object($value)) {
            // Vérifier les références circulaires
            $hash = spl_object_hash($value);
            if (isset($visited[$hash])) {
                return '[Circular Reference: ' . get_class($value) . ']';
            }
            $visited[$hash] = true;
            
            // Si l'objet implémente JsonSerializable
            if ($value instanceof JsonSerializable) {
                return $value->jsonSerialize();
            }
            
            $result = [
                '__class' => get_class($value)
            ];
            
            // Utiliser la réflexion pour accéder aux propriétés (même privées/protégées)
            $reflection = new ReflectionClass($value);
            
            foreach ($reflection->getProperties() as $property) {
                $propName = $property->getName();
                
                try {
                    $propValue = $property->getValue($value);
                    $result[$propName] = convert_to_serializable($propValue, $depth + 1, $visited);
                } catch (Error $e) {
                    $result[$propName] = '[Uninitialized]';
                }
            }
            
            unset($visited[$hash]);
            return $result;
        }
        
        return '[Unknown type]';
    }
}