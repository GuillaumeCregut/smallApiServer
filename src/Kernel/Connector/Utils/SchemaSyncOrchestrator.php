<?php

namespace App\Kernel\Connector\Utils;

use App\Kernel\Connector\Utils\Scanner\PivotTableScanner;
use App\Kernel\Connector\Utils\Scanner\AbstractScannerDriver;

class SchemaSyncOrchestrator
{
    private SchemaComparator $comparator;
    private PivotSchemaComparator $pivotComparator;
    private PivotTableScanner $pivotScanner;

    public function __construct(private AbstractScannerDriver $driver)
    {
        $this->comparator = new SchemaComparator();
        $this->pivotComparator = new PivotSchemaComparator($driver);
        $this->pivotScanner = new PivotTableScanner($driver);
    }

    public function run(array $entitySchema, array $expectedPivots): array
    {
        $allDbTables = $this->driver->getTables();
        $scanResult = $this->pivotScanner->scan($expectedPivots);
        $this->driver->excludeTables(
            $this->pivotScanner->getKnownPivotTableNames($expectedPivots)
        );
        $dbSchema = $this->driver->scan();
        $entityDiff = $this->comparator->compare($entitySchema, $dbSchema);
        $pivotDiff = $this->pivotComparator->compare(
            $expectedPivots,
            $scanResult,
            $allDbTables
        );

        return [
            'entities' => $entityDiff,
            'pivots'   => $pivotDiff,
        ];
    }

    public function isInSync(array $result): bool
    {
        return $this->comparator->isInSync($result['entities'])
            && $this->pivotComparator->isInSync($result['pivots']);
    }
}
