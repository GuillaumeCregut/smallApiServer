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
        if (str_ends_with($name, 'Entity')) {
            $shortName = substr($name,0,-6);
        }
        $content = $this->getRepositoryTemplate($name, $shortName);
        $folder = $this->appPath . 'src' . DIRECTORY_SEPARATOR . 'Repository';
        $filename = "{$shortName}Repository.php";
        return ConsoleHelper::saveToFile($folder, $filename, $content);
    }

    private function getRepositoryTemplate(string $name, string $shortname): string
    {
        $repoName = "{$shortname}Repository";
        $entityName = "{$name}";
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
