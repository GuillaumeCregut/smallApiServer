<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Bin\Database;

class EntityModifier
{
    public static function addProperty(
        string $filePath,
        string $propertyName,
        string $propertyType,
        array $useStatements,
        array $attributes = [], 
        ?string $typeRelation = null,
        ?string $nameRelation = null
    ): bool {
        $content = file_get_contents($filePath);
        $tokens = token_get_all($content);
        foreach ($useStatements as $useStatement) {
            if (strpos($content, "use {$useStatement};") === false) {
                $content = self::AddUseNewClass($tokens, $content, $useStatement);
                $tokens = token_get_all($content);
            }
        }
        $lastPropertyPos = self::findLastPropertyPosition($tokens, $content);

        $propertyBlock = self::buildPropertyBlock($propertyName, $propertyType, $attributes);

        $methods = self::buildGetterSetter($propertyName, $propertyType, $typeRelation, $nameRelation);

        $lastBracePos = strrpos($content, '}');

        $content = substr_replace($content, $propertyBlock, $lastPropertyPos, 0);

        $lastBracePos = strrpos($content, '}');
        $content = substr_replace($content, $methods, $lastBracePos, 0);

        $result = file_put_contents($filePath, $content);
        if ($result > 0) {
            return true;
        }
        return false;
    }

    private static function AddUseNewClass(array $tokens, string $content, string $useStatement): string
    {
        $lastUsePos = 0;
        $namespaceEndPos = 0;

        foreach ($tokens as $token) {
            if (!is_array($token)) continue;

            if ($token[0] === T_NAMESPACE) {
                $namespaceEndPos = strpos($content, ';', strpos($content, $token[1])) + 1;
            }

            if ($token[0] === T_USE) {
                $useLineEnd = strpos($content, ';', strpos($content, $token[1], $lastUsePos)) + 1;
                $lastUsePos = $useLineEnd;
            }
        }

        $insertPos = $lastUsePos !== 0 ? $lastUsePos : $namespaceEndPos;
        $newUse = "\nuse {$useStatement};";
        $content = substr_replace($content, $newUse, $insertPos, 0);
        return $content;
    }

    private static function findLastPropertyPosition(array $tokens, string $content): int
    {
        $lineNumber = 0;
        $prevSignificantToken = null;
        $prevPrevSignificantToken = null;

        foreach ($tokens as $token) {
            if (!is_array($token)) continue;
            if ($token[0] === T_WHITESPACE) continue;

            if (
                $token[0] === T_VARIABLE &&
                $token[1] !== '$this' &&
                in_array($prevSignificantToken, [T_STRING, T_NAME_QUALIFIED]) &&
                in_array($prevPrevSignificantToken, [T_PRIVATE, T_PROTECTED, T_PUBLIC])
            ) {
                $lineNumber = $token[2];
            }

            $prevPrevSignificantToken = $prevSignificantToken;
            $prevSignificantToken = $token[0];
        }

        if ($lineNumber === 0) {
            return self::findClassOpeningBrace($content);
        }

        $lines = explode("\n", $content);
        $pos = 0;
        for ($i = 0; $i < $lineNumber; $i++) {
            $pos += strlen($lines[$i]) + 1;
        }
        $pos += strlen($lines[$lineNumber]) + 1;

        return $pos;
    }
    private static function findClassOpeningBrace(string $content): int
    {
        $pos = strpos($content, '{');
        return $pos + 1;
    }

    private static function buildPropertyBlock(
        string $name,
        string $type,
        array $attributes
    ): string {
        $block = "";
        foreach ($attributes as $attribute) {
            $block .= "    {$attribute}\n";
        }
        $block .= "    private ?{$type} \${$name} = null;\n\n";
        return $block;
    }

    private static function buildGetterSetter(string $name, string $type, ?string $relationType, ?string $relationName): string
    {
        $ucName = ucfirst($name);
        $getters = <<<GETTER
        public function get{$ucName}(): {$type}
        {
            return \$this->{$name};
        }\n
    GETTER;
    $setters = <<<SETTER
    public function set{$ucName}({$type} \${$name}): self
    {
        \$this->{$name} = \${$name};
        return \$this;
    }\n
SETTER;
    if('m' === $relationType){
         $setters = <<<SETTER
        public function set{$ucName}({$type} \${$name}): self
        {
            \$this->{$name} = \${$name};
            \$this->syncRelation('{$name}', \${$name});
            return \$this;
        }\n
    SETTER;   

    }
    if('o' === $relationType) {
        $getters .= "\n" .<<<GETTER
        public function getOne{$ucName}(int \$index): {$relationName}
        {
            return \$this->{$name}->get(\$index);
        }\n
    GETTER;

    $setters .= <<<SETTER
        
        public function Add{$ucName}({$relationName} \${$name}): self
        {
            \$this->{$name}->addToCollection(\${$name});
            return \$this;
        }      
        
        public function remove{$ucName}({$relationName} \${$name}): self
        {
            \$this->{$name}->remove(\${$name});
            return \$this;
        }\n
    SETTER;
    }
    $getterSetter ="\n" . $getters . "\n" . $setters;
    return $getterSetter;
    }
}
