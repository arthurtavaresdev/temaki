<?php

namespace ArthurTavaresDev\Temaki\Database\SqliteS3;

use Exception;
use Illuminate\Database\Connectors\SQLiteConnector;
use PDO;
use RuntimeException;

class SqliteS3Connector extends SQLiteConnector
{
    /**
     * @param array<string, mixed> $config
     *
     * @throws Exception
     * @see \Illuminate\Database\Connectors\SQLiteConnector::connect()
     */
    public function connect(array $config): PDO
    {
        $options = $this->getOptions($config);

        if (str_starts_with($config['database'] ?? '', 'temaki:')) {
            return $this->createConnection($config['database'], $config, $options);
        }

        return parent::connect($config);
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function createPdoConnection($dsn, $username, $password, $options): PDO
    {
        if (str_starts_with($dsn, 'temaki:')) {
            $matches = explode(':', $dsn, 3);
            if (blank($matches)) {
                throw new RuntimeException('Could not parse DSN: ' . $dsn);
            }
            $path = $matches[1];
            return new PDOSQLiteS3($path);
        }

        return parent::createPdoConnection($dsn, $username, $password, $options);
    }
}
