<?php

declare(strict_types=1);

namespace VDauchy\SqlAnalyzer\queries;

class Pdo implements Query
{
    private \PDO $connection;
    private string $query;
    private array $bindings;
    private ?float $time;

    /**
     * Pdo constructor.
     * @param  \PDO  $connection
     * @param  string  $query
     * @param  array  $bindings
     * @param  float|null  $time
     */
    public function __construct(\PDO $connection, string $query, array $bindings = [], ?float $time = null)
    {
        $this->connection = $connection;
        $this->query = $query;
        $this->bindings = $bindings;
        $this->time = $time;
    }

    /**
     * @return string
     */
    public function hash(): string
    {
        return md5($this->query);
    }

    /**
     * @param  string  $query
     * @return array
     */
    public function run(string $query): array
    {
        return tap(
            $this->connection->prepare($query),
            fn($prepare) => $prepare->execute($this->bindings)
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @return string
     */
    public function engine(): string
    {
        return $this->connection->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    /**
     * @return string
     */
    public function query(): string
    {
        return $this->query;
    }

    /**
     * @return float|null
     */
    public function time(): ?float
    {
        return $this->time;
    }
}
