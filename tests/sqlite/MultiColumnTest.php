<?php

declare(strict_types=1);

namespace VDauchy\SqlAnalyzer\Tests\sqlite;

use VDauchy\SqlAnalyzer\Tests\TestCase;

class MultiColumnTest extends TestCase
{
    public function testMultiBetweenWithoutIndexes()
    {
        $db = $this->sqlite();
        $db->exec("CREATE TABLE fooTable(a INTEGER, b INTEGER)");

        $analysis = $this->analyse($this->query($db, "select * from fooTable where (a BETWEEN 1 AND 2) OR (b BETWEEN 3 AND 4)"));

        $this->assertFalse($analysis->isOptimized(), $analysis->explain());
    }

    public function testMultiBetweenWithIndexes()
    {
        $db = $this->sqlite();
        $db->exec("CREATE TABLE fooTable(a INTEGER, b INTEGER)");
        $db->exec("CREATE INDEX idx_a ON fooTable (a)");
        $db->exec("CREATE INDEX idx_b ON fooTable (b)");

        $analysis = $this->analyse($this->query($db, "select * from fooTable where (a BETWEEN 1 AND 2) OR (b BETWEEN 3 AND 4)"));

        $this->assertTrue($analysis->isOptimized(), $analysis->explain());
    }
}
