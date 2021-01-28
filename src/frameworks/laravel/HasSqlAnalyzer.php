<?php

declare(strict_types=1);

namespace VDauchy\SqlAnalyzer\frameworks\laravel;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\TestCase;
use VDauchy\SqlAnalyzer\queries\Pdo;
use VDauchy\SqlAnalyzer\SqlAnalyzer;

/**
 * @mixin TestCase
 */
trait HasSqlAnalyzer
{
    /**
     * @var bool
     */
    protected bool $isAnalyzerExecuting = false;

    /**
     * @var bool
     */
    protected bool $isAnalyzerActive = true;

    /**
     * @var array
     */
    protected array $explainedQueriesCache = [];

    /**
     * @var array
     */
    protected array $queries = [];

    /**
     * @var SqlAnalyzer
     */
    protected SqlAnalyzer $sqlAnalyzer;

    /**
     * Setup the test environment.
     *
     * @return SqlAnalyzer
     * @throws \Exception
     */
    protected function analyzerSetUp(): SqlAnalyzer
    {
        $this->explainedQueriesCache = [];
        $this->analyzerQueryCountReset();
        $this->sqlAnalyzer = new SqlAnalyzer();
        DB::getFacadeRoot()->listen(function (QueryExecuted $queryExecuted) {
            if (!$this->isAnalyzerExecuting) {
                $this->isAnalyzerExecuting = true;
                try {
                    $query = new Pdo(
                        $queryExecuted->connection->getPdo(),
                        $queryExecuted->sql,
                        $queryExecuted->bindings
                    );
                    if ($this->isAnalyzerActive) {
                        $analyzer = $this->explainedQueriesCache[$query->hash()] ??= $this->sqlAnalyzer->analyze($query);
                        $this->assertTrue($analyzer->isOptimized(), $analyzer->explain());
                    }
                    $this->queries[] = $query->query();
                } finally {
                    $this->isAnalyzerExecuting = false;
                }
            }
        });
        return $this->sqlAnalyzer;
    }

    /**
     *
     */
    protected function analyzerActivate()
    {
        $this->isAnalyzerActive = true;
    }

    /**
     *
     */
    protected function analyzerDeactivate()
    {
        $this->isAnalyzerActive = false;
    }

    /**
     * @return int
     */
    protected function analyzerQueryCount(): int
    {
        return count($this->queries);
    }

    /**
     *
     */
    protected function analyzerQueryCountReset(): void
    {
        $this->queries = [];
    }

    /**
     * @param  callable  $callable
     * @return
     */
    protected function analyzerJumpOver(callable $callable)
    {
        $this->analyzerDeactivate();
        $result = $callable();
        $this->analyzerDeactivate();
        return $result;
    }

    /**
     * @return array
     */
    protected function queries()
    {
        return $this->queries;
    }
}
