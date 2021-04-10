<?php

declare(strict_types=1);

namespace VDauchy\SqlAnalyzer\analyzers;

use VDauchy\SqlAnalyzer\queries\Query;

abstract class Base implements Analyzer
{
    /**
     * @var Query
     */
    protected Query $query;

    /**
     * @var array
     */
    protected array $ignore = [];

    /**
     * @var array|null
     */
    protected ?array $explains = null;

    /**
     * @var array|null
     */
    protected ?array $missingOptimizations = null;

    /**
     * @var array|null
     */
    protected ?array $ignoredMissingOptimizations = null;

    /**
     * @param array $explain
     * @return bool
     */
    abstract protected function isIgnored(array $explain): bool;

    /**
     * @param $explain
     * @return bool
     */
    abstract protected function checkIsOptimized(array $explain): bool;

    /**
     * @return array
     */
    abstract protected function formatExplains(): array;

    /**
     * @param Query $query
     * @param array $ignore
     */
    final public function __construct(Query $query, array $ignore = [])
    {
        $this->query = $query;
        $this->ignore = $ignore;
    }

    /**
     * @return array
     */
    final public function explains(): array
    {
        return $this->explains ??= $this->cast($this->isExplainQuery() ? [] : $this->query->run($this->explainQuery()));
    }

    /**
     * @return array
     */
    final public function missingOptimizations(): array
    {
        if (is_null($this->missingOptimizations)) {
            $this->analyze();
        }
        return $this->missingOptimizations;
    }

    /**
     * @return array
     */
    final public function ignoredMissingOptimizations(): array
    {
        if (is_null($this->ignoredMissingOptimizations)) {
            $this->analyze();
        }
        return $this->ignoredMissingOptimizations;
    }

    /**
     * @return bool
     */
    final public function isOptimized(): bool
    {
        return (count($this->missingOptimizations()) === 0);
    }

    /**
     * @return string
     */
    final public function explain(): string
    {
        return <<<TEXT
        | MISSING OPTIMIZATION DETECTED |\n
        QUERY: "{$this->query->query()}"\n
        TIME: {$this->toJson($this->query->time() ?? 'Unknown')} milliseconds\n
        EXPLAINS: {$this->toJson($this->formatExplains())}\n
        MISSING OPTIMIZATIONS: {$this->toJson($this->missingOptimizations())}\n
        IGNORED MISSING OPTIMIZATIONS: {$this->toJson($this->ignoredMissingOptimizations())}\n
        TEXT;
    }

    /**
     * @return bool
     */
    final protected function isExplainQuery(): bool
    {
        return str_starts_with(strtoupper($this->query->query()), 'EXPLAIN');
    }

    /**
     * @return string
     */
    final protected function explainQuery(): string
    {
        if ($this->query->engine() === SQLite::DRIVER) {
            return "EXPLAIN QUERY PLAN {$this->query->query()}";
        } else {
            return "EXPLAIN EXTENDED {$this->query->query()}";
        }
    }

    /**
     * @param array $old
     * @return array
     */
    final protected function cast(array $old): array
    {
        $new = [];
        foreach ($old as $key => $value) {
            $new[$key] = (is_object($value) || is_array($value)) ? $this->cast((array)$value) : (string)$value;
        }
        return $new;
    }

    /**
     *
     */
    final protected function analyze(): void
    {
        $this->missingOptimizations = [];
        $this->ignoredMissingOptimizations = [];
        foreach ($this->explains() as $explain) {
            if ($this->isIgnored($explain)) {
                $this->ignoredMissingOptimizations[] = $explain;
                continue;
            }
            if (! $this->checkIsOptimized($explain)) {
                $this->missingOptimizations[] = $explain;
            }
        }
    }

    /**
     * @param  mixed  $data
     * @return string
     */
    final protected function toJson($data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }
}
