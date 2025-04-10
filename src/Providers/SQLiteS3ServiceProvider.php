<?php

namespace ArthurTavaresDev\Temaki\Providers;

use ArthurTavaresDev\Temaki\Database\SqliteS3\SqliteS3Connector;
use Illuminate\Support\ServiceProvider;

class SQLiteS3ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->publishes([
            __DIR__.'/../../config/temaki.php' => config_path('temaki.php'),
        ], 'temaki-config');


        // Override the default sqlite connector with our own
        $this->app->bind('db.connector.sqlite', SqliteS3Connector::class);
    }
}
