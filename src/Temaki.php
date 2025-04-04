<?php

namespace ArthurTavaresDev\Temaki;

use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\SQLiteBuilder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait Temaki
{
    protected static $temakiConnection;

    public function getRows()
    {
        return $this->rows;
    }

    public function getSchema()
    {
        return $this->schema ?? [];
    }

    protected function temakiCacheReferencePath(): false|string
    {
        return (new \ReflectionClass(static::class))->getFileName();
    }


    public static function resolveConnection($connection = null)
    {
        return static::$temakiConnection;
    }

    protected function temakiCachePath(): string
    {
        $bucket = config('filesystems.disks.s3.bucket');
        $fileName = $this->temakiCacheFileName();

        if(! Storage::disk('s3')->exists($fileName)) {
            Storage::disk('s3')->put($fileName, '');
        }


        return "s3://{$bucket}/{$fileName}";
    }

    protected function temakiCacheFileName()
    {
        return config('temaki.cache-prefix', 'temaki').'-'.Str::kebab(str_replace('\\', '', static::class)).'.sqlite';
    }

    public static function bootTemaki(): void
    {
        $instance = (new static);
        static::setSqliteConnection($instance->temakiCachePath());
        $instance->migrate();
    }

    public function fresh($with = []): ?self
    {
        $this->getSchemaBuilder()->dropIfExists($this->getTable());
        $this->migrate();

        return parent::fresh($with);
    }

    protected function newRelatedInstance($class)
    {
        return tap(new $class, function ($instance) {
            if (!$instance->getConnectionName()) {
                $instance->setConnection($this->getConnectionResolver()?->getDefaultConnection());
            }
        });
    }

    protected static function setSqliteConnection($database)
    {
        $config = [
            'driver' => 'sqlite',
            'database' => $database,
        ];

        static::$temakiConnection = app(ConnectionFactory::class)->make($config);
        app('config')->set('database.connections.'.static::class, $config);
    }

    public function migrate(): void
    {
        $rows = $this->getRows();
        $tableName = $this->getTable();

        if (count($rows)) {
            $this->createTable($tableName, $rows[0]);
        } else {
            $this->createTableWithNoData($tableName);
        }

        foreach (array_chunk($rows, $this->getSushiInsertChunkSize()) ?? [] as $inserts) {
            self::upsert($inserts, [$this->primaryKey]);
        }
    }

    public function createTable(string $tableName, $firstRow): void
    {
        $this->createTableSafely($tableName, function ($table) use ($firstRow) {
            // Add the "id" column if it doesn't already exist in the rows.
            if ($this->incrementing && ! array_key_exists($this->primaryKey, $firstRow)) {
                $table->increments($this->primaryKey);
            }

            foreach ($firstRow as $column => $value) {
                $type = match (true) {
                    is_int($value) => 'integer',
                    is_numeric($value) => 'float',
                    $value instanceof \DateTime => 'dateTime',
                    default => 'string',
                };

                if ($column === $this->primaryKey) {
                    if($type === 'integer') {
                        $table->increments($this->primaryKey);
                        continue;
                    }

                    if($this->keyType === 'string') {
                        $table->string($this->primaryKey)->primary();
                        continue;
                    }
                }

                $schema = $this->getSchema();

                $type = $schema[$column] ?? $type;

                $table->{$type}($column)->nullable();
            }

            if ((!array_key_exists('updated_at', $firstRow) || !array_key_exists('created_at', $firstRow)) && $this->usesTimestamps()) {
                $table->timestamps();
            }

            $this->afterMigrate($table);
        });
    }

    protected function afterMigrate(BluePrint $table): void
    {
        //
    }

    public function createTableWithNoData(string $tableName): void
    {
        $this->createTableSafely($tableName, function ($table) {
            $schema = $this->getSchema();

            if ($this->incrementing && ! in_array($this->primaryKey, array_keys($schema))) {
                $table->increments($this->primaryKey);
            }

            foreach ($schema as $name => $type) {
                if ($name === $this->primaryKey && $type == 'integer') {
                    $table->increments($this->primaryKey);
                    continue;
                }

                $table->{$type}($name)->nullable();
            }

            if ($this->usesTimestamps() && (! in_array('updated_at', array_keys($schema)) || ! in_array('created_at', array_keys($schema)))) {
                $table->timestamps();
            }
        });
    }

    protected function getSchemaBuilder(): SQLiteBuilder
    {
        return static::resolveConnection()->getSchemaBuilder();
    }

    protected function createTableSafely(string $tableName, Closure $callback): void
    {
        $schemaBuilder = $this->getSchemaBuilder();

        try {
            $schemaBuilder->create($tableName, $callback);
        } catch (QueryException $e) {
            if (Str::contains($e->getMessage(), [
                'already exists (SQL: create table',
                sprintf('table "%s" already exists', $tableName),
            ])) {
                // This error can happen in rare circumstances due to a race condition.
                // Concurrent requests may both see the necessary preconditions for
                // the table creation, but only one can actually succeed.
                return;
            }

            throw $e;
        }
    }

    public function usesTimestamps(): bool
    {
        // Override the Laravel default value of $timestamps = true; Unless otherwise set.
        return (new \ReflectionClass($this))->getProperty('timestamps')->class === static::class && parent::usesTimestamps();
    }

    public function getSushiInsertChunkSize() {
        return $this->temakiInsertChunkSize ?? 100;
    }

    public function getConnectionName(): string
    {
        return static::class;
    }
}