<?php

namespace ArthurTavaresDev\Temaki\Providers;

use ArthurTavaresDev\Temaki\Database\SqliteS3\SqliteS3Connector;
use Illuminate\Support\ServiceProvider;

class SQLiteS3ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Override the default sqlite connector with our own
        $this->app->bind('db.connector.sqlite', SqliteS3Connector::class);
    }
}