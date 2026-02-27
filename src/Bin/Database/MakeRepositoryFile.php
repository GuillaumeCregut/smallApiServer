<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Bin\Database;

use App\Bin\ConsoleHelper;

class MakeRepositoryFile
{
    public function __construct(private string $appPath) {}

    public function createRepositoryFile(string $name): bool
    {
        $content = $this->getRepositoryTemplate($name);
        $folder = $this->appPath . 'src' . DIRECTORY_SEPARATOR . 'Repository';
        $filename = "{$name}Repository.php";
        return ConsoleHelper::saveToFile($folder, $filename, $content);
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
}
