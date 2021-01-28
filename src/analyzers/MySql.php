<?php

declare(strict_types=1);

namespace VDauchy\SqlAnalyzer\analyzers;

class MySql extends Base
{
    public const DRIVER = 'mysql';

    /**
     * @param array $explain
     * @return bool
     */
    protected function isIgnored(array $explain): bool
    {
        return false;
    }

    protected function checkIsOptimized(array $explain): bool
    {
        if ($this->isUsingIndexQuery($explain)) {
            return true;
        }
        if ($this->isImpossibleQuery($explain)) {
            return true;
        }
        if ($this->isScanTableQuery($explain)) {
            return true;
        }
        if ($this->isCompoundQuery($explain)) {
            return true;
        }
        return false;
    }

    /**
     * @param $explain
     * @return bool
     */
    protected function isScanTableQuery(array $explain): bool
    {
        return
            str_starts_with($explain['type'], 'ALL') && !str_contains($explain['Extra'] ?? '', 'Using where');
    }

    /**
     * @param $explain
     * @return bool
     */
    protected function isUsingIndexQuery(array $explain): bool
    {
        return !empty($explain['key']);
    }

    /**
     * @param $explain
     * @return bool
     */
    protected function isImpossibleQuery(array $explain): bool
    {
        return
            str_starts_with($explain['Extra'], 'Impossible WHERE');
    }

    /**
     * @param $explain
     * @return bool
     */
    protected function isCompoundQuery(array $explain): bool
    {
        return str_contains($explain['select_type'], 'UNION RESULT');
    }

    /**
     * @return array
     */
    protected function formatExplains(): array
    {
        return $this->explains();
    }
}
