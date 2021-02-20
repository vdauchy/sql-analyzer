<?php

declare(strict_types=1);

namespace VDauchy\SqlAnalyzer\frameworks\laravel;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Collection;
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
     * @var array
     */
    protected array $extraIgnore = [];

    /**
     * Setup the test environment.
     * @param  array  $ignore
     * @return SqlAnalyzer
     */
    protected function analyzerSetUp(array $ignore = []): SqlAnalyzer
    {
        $this->explainedQueriesCache = [];
        $this->analyzerQueryCountReset();
        $this->sqlAnalyzer = new SqlAnalyzer($ignore);
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
                        $analyzer = $this->explainedQueriesCache[$query->hash()]
                            ??= $this->sqlAnalyzer->analyze($query, $this->extraIgnore);
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
    protected function analyzerActivate(): void
    {
        $this->isAnalyzerActive = true;
    }

    /**
     *
     */
    protected function analyzerDeactivate(): void
    {
        $this->isAnalyzerActive = false;
    }

    /**
     * @return int
     */
    protected function analyzerQueryCount(): int
    {
        return $this->analyzerQueries()->count();
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
     * @return mixed
     */
    protected function analyzerJumpOver(callable $callable)
    {
        if ($this->isAnalyzerActive) {
            $this->analyzerDeactivate();
            $result = $callable();
            $this->analyzerActivate();
        } else {
            $result = $callable();
        }
        return $result;
    }

    /**
     * @return Collection
     */
    protected function analyzerQueries(): Collection
    {
        return collect($this->queries);
    }

    /**
     * @param  array  $extraIgnore
     */
    protected function analyzerIgnore(array $extraIgnore): void
    {
        $this->extraIgnore = $extraIgnore;
    }

    /**
     * @param int $expectedCount
     * @param callable $callable
     * @return mixed
     */
    protected function assertDatabaseQueryCountEquals(int $expectedCount, callable $callable)
    {
        if ($this->isAnalyzerActive) {
            $oldQueries = $this->analyzerQueries();
            $result = $callable();
            $newQueries = $this->analyzerQueries();
            $this->assertEquals(
                $expectedCount,
                $newQueries->count() - $oldQueries->count(),
                json_encode($newQueries->diff($oldQueries)),
            );
        } else {
            $result = $callable();
        }
        return $result;
    }
}
