<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    // Si necesitas que siempre corra las migraciones de tu ruta v2:
    protected function setUp(): void
    {
        parent::setUp();

        // Asegura que las migraciones de la v2 se ejecuten en las pruebas
        Artisan::call('migrate', [
            '--database' => 'mysql',
            '--path' => 'database/migrations/v2',
        ]);
    }
}
