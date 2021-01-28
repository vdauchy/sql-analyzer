<?php

declare(strict_types=1);

namespace VDauchy\SqlAnalyzer\queries;

interface Query
{
    /**
     * @return string
     */
    public function hash(): string;

    /**
     * @param string $query
     * @return array
     */
    public function run(string $query): array;

    /**
     * @return string
     */
    public function engine(): string;

    /**
     * @return string
     */
    public function query(): string;

    /**
     * @return float
     */
    public function time(): ?float;
}
