<?php

declare(strict_types=1);

namespace VDauchy\SqlAnalyzer\Tests;

use Exception;
use PDO;
use VDauchy\SqlAnalyzer\analyzers\Analyzer;
use VDauchy\SqlAnalyzer\queries\Query;
use VDauchy\SqlAnalyzer\SqlAnalyzer;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @return PDO
     */
    protected function sqlite(): PDO
    {
        return tap(new PDO('sqlite::memory:'), function (PDO $connection) {
            $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        });
    }

    /**
     * @param  string  $dbName
     * @param  string  $user
     * @param  string  $password
     * @return PDO
     */
    protected function mysql(string $dbName = 'test', string $user = 'test', string $password = 'test'): PDO
    {
        $sqlCommandGenerator = "
            SELECT concat('DROP TABLE IF EXISTS `', table_name, '`;') as command
            FROM information_schema.tables
            WHERE table_schema = '{$dbName}';";
        return $this->retry(fn() => tap(
            new PDO("mysql:host=mysql;dbname={$dbName}", $user, $password),
            function (PDO $connection) use ($sqlCommandGenerator) {
                $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $connection->exec('SET FOREIGN_KEY_CHECKS = 0;');
                foreach ($connection->query($sqlCommandGenerator)->fetchAll(PDO::FETCH_COLUMN, 'command') as $command) {
                    $connection->exec($command);
                };
                $connection->exec('SET FOREIGN_KEY_CHECKS = 1;');
            }
        ));
    }

    /**
     * @param  PDO  $engine
     * @param  string  $query
     * @param  array  $bindings
     * @return Query
     */
    protected function query(PDO $engine, string $query, array $bindings = []): Query
    {
        return new \VDauchy\SqlAnalyzer\queries\Pdo($engine, $query, $bindings);
    }

    /**
     * @param  Query  $query
     * @return Analyzer
     * @throws Exception
     */
    protected function analyse(Query $query): Analyzer
    {
        return (new SqlAnalyzer())->analyze($query);
    }

    /**
     * @param  callable  $callable
     * @param  int  $retries
     * @return mixed
     * @throws Exception
     */
    private function retry(callable $callable, int $retries = 10)
    {
        $fails = [];
        do {
            try {
                return $callable();
            } catch (\PDOException $PDOException) {
                $fails[] = $PDOException;
                usleep(100000 * count($fails));
            }
        } while (count($fails) < $retries);
        throw new Exception("Could not connect to the database error: " . end($fails)->getMessage());
    }
}
