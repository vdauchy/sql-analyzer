<?php

declare(strict_types=1);

namespace VDauchy\SqlAnalyzer\Tests\mysql;

use VDauchy\SqlAnalyzer\Tests\TestCase;

class SingleColumnTest extends TestCase
{
    public function testNoWhere()
    {
        $db = $this->mysql();
        $db->exec("CREATE TABLE fooTable(id INTEGER)");

        $analysis = $this->analyse($this->query($db, "select * from fooTable"));

        $this->assertTrue($analysis->isOptimized(), $analysis->explain());
    }

    public function testPingWhere()
    {
        $db = $this->mysql();
        $db->exec("CREATE TABLE fooTable(id INTEGER)");

        $analysis = $this->analyse($this->query($db, "select * from fooTable where 1 = 0"));

        $this->assertTrue($analysis->isOptimized(), $analysis->explain());

        $analysis = $this->analyse($this->query($db, "select * from fooTable where 0 = 1"));

        $this->assertTrue($analysis->isOptimized(), $analysis->explain());
    }

    public function testSingleColumnWhereWithoutIndex()
    {
        $db = $this->mysql();
        $db->exec("CREATE TABLE fooTable(id INTEGER)");

        $analysis = $this->analyse($this->query($db, "select * from fooTable where id = 5"));

        $this->assertFalse($analysis->isOptimized(), $analysis->explain());
    }

    public function testSingleColumnWhereWithIndex()
    {
        $db = $this->mysql();
        $db->exec("CREATE TABLE fooTable(id INTEGER PRIMARY KEY)");
        $db->exec("INSERT INTO fooTable(id) VALUES (1)");

        $analysis = $this->analyse($this->query($db, "select * from fooTable where id = 1"));

        $this->assertTrue($analysis->isOptimized(), $analysis->explain());
    }
}
