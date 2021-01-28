<?php

declare(strict_types=1);

namespace VDauchy\SqlAnalyzer\Tests\sqlite;

use VDauchy\SqlAnalyzer\Tests\TestCase;

class UnionQueryTest extends TestCase
{
    public function testNoWhere()
    {
        $db = $this->sqlite();
        $db->exec("CREATE TABLE foo(id INTEGER)");
        $db->exec("CREATE INDEX idx_foo ON foo (id)");
        $db->exec("CREATE TABLE bar(
            a_foo_id INTEGER, 
            b_foo_id INTEGER, 
            FOREIGN KEY(a_foo_id) REFERENCES foo(id),
            FOREIGN KEY(b_foo_id) REFERENCES foo(id))");
        $db->exec("CREATE INDEX idx_bar_a ON bar(a_foo_id)");
        $db->exec("CREATE INDEX idx_bar_b ON bar(b_foo_id)");

        $analysis = $this->analyse($this->query($db, <<<SQL
            select * 
            from (
                select distinct foo.*, bar.b_foo_id, bar.a_foo_id
                from foo 
                inner join bar on foo.id = bar.a_foo_id 
                where bar.b_foo_id = ?) as bar_b
            union 
            select * 
            from (
                select * 
                from foo 
                inner join bar on foo.id = bar.b_foo_id 
                where bar.a_foo_id = ?) as bar_a
        SQL, [0, 0]));

        $this->assertTrue($analysis->isOptimized(), $analysis->explain());
    }
}
