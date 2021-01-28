<?php

declare(strict_types=1);

namespace VDauchy\SqlAnalyzer\Tests\mysql;

use VDauchy\SqlAnalyzer\Tests\TestCase;

class MultiColumnTest extends TestCase
{
    public function testMultiBetweenWithoutIndexes()
    {
        $db = $this->mysql();
        $db->exec("CREATE TABLE fooTable(a INTEGER, b INTEGER)");

        $analysis = $this->analyse($this->query($db, "select * from fooTable where (a BETWEEN 1 AND 2) OR (b BETWEEN 3 AND 4)"));

        $this->assertFalse($analysis->isOptimized(), $analysis->explain());
    }

    public function testMultiBetweenWithIndexes()
    {
        $db = $this->mysql();
        $db->exec("CREATE TABLE fooTable(a INTEGER, b INTEGER)");
        $db->exec("CREATE INDEX idx_compound ON fooTable (a, b)");

        $analysis = $this->analyse($this->query($db, "select * from fooTable where (a BETWEEN 1 AND 2) OR (b BETWEEN 3 AND 4)"));

        $this->assertTrue($analysis->isOptimized(), $analysis->explain());
    }
}
