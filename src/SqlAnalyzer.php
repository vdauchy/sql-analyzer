<?php

declare(strict_types=1);

namespace VDauchy\SqlAnalyzer;

use Exception;
use VDauchy\SqlAnalyzer\analyzers\MySql;
use VDauchy\SqlAnalyzer\queries\Query;
use VDauchy\SqlAnalyzer\analyzers\Analyzer;
use VDauchy\SqlAnalyzer\analyzers\SQLite;

class SqlAnalyzer
{
    /**
     * @var array
     */
    protected array $ignore = [];

    /**
     * @param  Query  $query
     * @return Analyzer
     * @throws Exception
     */
    public function analyze(Query $query): Analyzer
    {
        return $this->buildQueryAnalyzer($query);
    }

    /**
     * @param array $ignore
     */
    public function ignore(array $ignore)
    {
        $this->ignore = $ignore;
    }

    /**
     * @param Query $query
     * @return Analyzer
     * @throws Exception
     */
    protected function buildQueryAnalyzer(Query $query): Analyzer
    {
        switch ($driver = $query->engine()) {
            case SQLite::DRIVER:
                return new SQLite($query, $this->ignore);
            case MySql::DRIVER:
                return new MySql($query, $this->ignore);
            default:
                throw new Exception("Driver:'{$driver}' not supported.");
        }
    }
}
