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
    protected array $ignore;

    /**
     * SqlAnalyzer constructor.
     * @param  array  $ignore
     */
    public function __construct(array $ignore = [])
    {
        $this->ignore = $ignore;
    }

    /**
     * @param  Query  $query
     * @param  array  $extraIgnore
     * @return Analyzer
     */
    public function analyze(Query $query, array $extraIgnore = []): Analyzer
    {
        switch ($driver = $query->engine()) {
            case SQLite::DRIVER:
                return new SQLite($query, [...$this->ignore, ...$extraIgnore]);
            case MySql::DRIVER:
                return new MySql($query, [...$this->ignore, ...$extraIgnore]);
            default:
                throw new Exception("Driver:'{$driver}' not supported.");
        }
    }

    /**
     * @param  array  $ignore
     */
    public function ignore(array $ignore)
    {
        $this->ignore = $ignore;
    }
}
