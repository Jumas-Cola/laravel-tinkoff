<?php

namespace Kenvel;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Kenvel\LaravelTinkoffClass
 */
class Tinkoff extends Facade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor()
    {
        return 'laraveltinkoff';
    }
}
