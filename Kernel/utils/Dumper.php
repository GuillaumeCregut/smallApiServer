<?php

namespace App\Kernel\utils;

class Dumper
{
    private static array $lines = [];

    public static function addLine(DumpLine $line): void
    {
        self::$lines[] = $line;
    }

    public static function getLines(): array
    {
        return self::$lines;
    }

    public static function displayHTML()
    {
        
        foreach (self::$lines as $line) {
            ob_start();
            // Assuming DumpLine has a __toString method for HTML representation
            echo "<div style='background: #fff3cd; border: 2px solid #ff9800; padding: 10px; margin: 10px 0; font-family: monospace;'>";
            echo "<div style='background: #ff9800; color: #fff; padding: 5px; margin: -10px -10px 10px -10px; font-weight: bold;'>";
            echo "🐛 DUMP at " . $line->line;
            echo "</div>";

            echo "<div style='background: #fff; border: 1px solid #ddd; padding: 10px; margin: 5px 0;'>";

            // Get variable type
            echo "<strong style='color: #d32f2f;'>Type:</strong> <span style='color: #1976d2;'>$line->type</span><br>";
             echo "<strong style='color: #d32f2f;'>Nom:</strong> <span style='color: #1976d2;'>$line->name</span><br>";
            $var = $line->value;
            // Display the variable
            if (is_bool($var)) {
                echo "<strong>Value:</strong> <span style='color: " . ($var ? '#4caf50' : '#f44336') . ";'>" .
                    ($var ? 'true' : 'false') . "</span>";
            } elseif (is_null($var)) {
                echo "<strong>Value:</strong> <span style='color: #9e9e9e;'>null</span>";
            } elseif (is_string($var)) {
                echo "<strong>Length:</strong> " . strlen($var) . "<br>";
                echo "<strong>Value:</strong> <pre style='margin: 5px 0; padding: 5px; background: #f5f5f5;'>" .
                    htmlspecialchars($var) . "</pre>";
            } elseif (is_array($var)) {
                echo "<strong>Count:</strong> " . count($var) . "<br>";
                echo "<strong>Value:</strong><pre style='margin: 5px 0; padding: 5px; background: #f5f5f5;'>";
                print_r($var);
                echo "</pre>";
            } elseif (is_object($var)) {
                echo "<strong>Class:</strong> <span style='color: #7b1fa2;'>" . get_class($var) . "</span><br>";
                echo "<strong>Value:</strong><pre style='margin: 5px 0; padding: 5px; background: #f5f5f5;'>";
                print_r($var);
                echo "</pre>";
            } else {
                echo "<strong>Value:</strong> <pre style='margin: 5px 0; padding: 5px; background: #f5f5f5;'>";
                var_dump($var);
                echo "</pre>";
            }

            echo "</div>";
            echo "</div>";
            $output = ob_get_clean();
            echo $output;
        }
    }

    public static function displayJSON(): void
    {
        header('Content-Type: application/json');
        foreach (self::$lines as $line) {
            // Convertir les objets en tableau pour le JSON
            $serializable = convert_to_serializable($line);
            echo json_encode($serializable, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            echo "\n";
        }
    }
}
