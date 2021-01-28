<?php

declare(strict_types=1);

namespace VDauchy\SqlAnalyzer\frameworks\laravel;

use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider as SupportServiceProvider;
use Barryvdh\Debugbar\Facade as DebugBar;
use VDauchy\SqlAnalyzer\analyzers\Analyzer;
use VDauchy\SqlAnalyzer\queries\Pdo;
use VDauchy\SqlAnalyzer\queries\Query;
use VDauchy\SqlAnalyzer\SqlAnalyzer;

class ServiceProvider extends SupportServiceProvider
{
    /**
     * @var array
     */
    private array $analyseCache = [];

    /**
     * @var SqlAnalyzer
     */
    private SqlAnalyzer $sqlAnalyzer;

    /**
     * @var array
     */
    private array $connections = [];

    /**
     * @var ConnectionFactory
     */
    private ConnectionFactory $connectionFactory;

    /**
     *
     */
    public function register()
    {
        $this->app->singleton('db.sql_analyzer', fn() => new SqlAnalyzer());
    }

    /**
     * @throws Exception
     */
    public function boot()
    {
        if (class_exists(DebugBar::class) && (DebugBar::isEnabled())) {
            $this->sqlAnalyzer = $this->app->make('db.sql_analyzer');
            $this->connectionFactory = $this->app->make('db.factory');
            DB::getFacadeRoot()->listen(function (QueryExecuted $queryExecuted) {
                if (DebugBar::isEnabled()) {
                    DebugBar::disable();
                    try {
                        $analyzer = $this->analyze($this->newQuery($queryExecuted));
                        if (!$analyzer->isOptimized()) {
                            DebugBar::warning("<pre>{$analyzer->explain()}</pre>");
                        }
                    } finally {
                        DebugBar::enable();
                    }
                }
            });
        }
    }

    /**
     * @param  QueryExecuted  $queryExecuted
     * @return Pdo
     */
    protected function newQuery(QueryExecuted $queryExecuted): Pdo
    {
        return new Pdo(
            $this->newConnection($queryExecuted)->getPdo(),
            $queryExecuted->sql,
            $queryExecuted->bindings,
            $queryExecuted->time
        );
    }

    /**
     * @param  QueryExecuted  $queryExecuted
     * @return Connection
     */
    protected function newConnection(QueryExecuted $queryExecuted): Connection
    {
        /* Create an independent connection to avoid breaking `lastInsertId` calls on inserted models */
        return $this->connections[$queryExecuted->connection->getName()] = $this->connectionFactory->make(
            $queryExecuted->connection->getConfig(),
            $queryExecuted->connection->getName()
        );
    }

    /**
     * @param  Query  $query
     * @return Analyzer
     * @throws Exception
     */
    protected function analyze(Query $query): Analyzer
    {
        return $this->analyseCache[$query->hash()] ??= $this->sqlAnalyzer->analyze($query);
    }
}
