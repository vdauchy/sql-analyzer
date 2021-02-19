<?php

declare(strict_types=1);

namespace VDauchy\SqlAnalyzer\analyzers;

final class SQLite extends Base
{
    public const DRIVER = 'sqlite';

    /**
     * @param array $explain
     * @return bool
     */
    protected function isIgnored(array $explain): bool
    {
        foreach ($this->ignore as $ignore) {
            if (str_contains(str_replace('"', '', $explain['detail']), $ignore)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $explain
     * @return bool
     */
    protected function checkIsOptimized(array $explain): bool
    {
        if ($this->isSubQuery($explain)) {
            return true;
        }
        if ($this->isCompoundQuery($explain)) {
            return true;
        }
        if ($this->isScanTableQuery($explain)) {
            return true;
        }
        if ($this->isUsingIndexQuery($explain)) {
            return true;
        }
        return false;
    }

    /**
     * @param $explain
     * @return bool
     */
    protected function isSubQuery(array $explain): bool
    {
        return
            str_starts_with($explain['detail'], 'LIST SUBQUERY') ||
            str_starts_with($explain['detail'], 'CORRELATED SCALAR SUBQUERY') ||
            str_starts_with($explain['detail'], 'LEFT-MOST SUBQUERY') ||
            str_starts_with($explain['detail'], 'SCALAR SUBQUERY');
    }

    /**
     * @param $explain
     * @return bool
     */
    protected function isCompoundQuery(array $explain): bool
    {
        return
            str_starts_with($explain['detail'], 'USE TEMP B-TREE') ||
            str_starts_with($explain['detail'], 'UNION USING TEMP B-TREE') ||
            str_starts_with($explain['detail'], 'COMPOUND QUERY') ||
            str_starts_with($explain['detail'], 'CO-ROUTINE') ||
            str_starts_with($explain['detail'], 'SCAN SUBQUERY');
    }

    /**
     * @param $explain
     * @return bool
     */
    protected function isUsingIndexQuery(array $explain): bool
    {
        return
            str_starts_with($explain['detail'], 'INDEX') ||
            str_starts_with($explain['detail'], 'MULTI-INDEX') ||
            str_contains($explain['detail'], 'USING INDEX') ||
            str_contains($explain['detail'], 'USING COVERING INDEX') ||
            str_contains($explain['detail'], 'USING INTEGER PRIMARY KEY');
    }

    /**
     * @param $explain
     * @return bool
     */
    protected function isScanTableQuery(array $explain): bool
    {
        return
            str_starts_with($explain['detail'], 'SCAN TABLE') && (
                (!str_contains($this->query->query(), 'where')) ||
                (str_contains(str_replace(' ', '', $this->query->query()), 'where0=1')) ||
                (str_contains(str_replace(' ', '', $this->query->query()), 'where1=0'))
            ) ||
            str_starts_with($explain['detail'], 'SCAN TABLE sqlite_sequence') ||
            str_starts_with($explain['detail'], 'SCAN CONSTANT ROW');
    }

    /**
     * @return array
     */
    protected function formatExplains(): array
    {
        $current = $this->explains();
        return $this->buildTree($current);
    }

    /**
     * @param array $explains
     * @param string $parentId
     * @return array
     */
    protected function buildTree(array $explains, string $parentId = '0'): array
    {
        $tree = [];
        foreach ($explains as $explain) {
            if ($explain['parent'] === $parentId) {
                $tree[$explain['detail']] = $this->buildTree($explains, $explain['id']);
            }
        }
        return $tree;
    }
}
