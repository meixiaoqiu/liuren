<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUpTraits()
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new \RuntimeException(sprintf(
                'Refusing to run database tests on [%s:%s]; expected [sqlite::memory:]. Clear Laravel configuration caches before running tests.',
                $connection,
                $database,
            ));
        }

        return parent::setUpTraits();
    }
}
