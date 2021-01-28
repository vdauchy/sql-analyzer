<?php

declare(strict_types=1);

namespace VDauchy\SqlAnalyzer\analyzers;

interface Analyzer
{
    public function isOptimized(): bool;

    public function explain(): string;

    public function explains(): array;
}
