<?php

namespace Datashaman\Elasticsearch\Model;

use Illuminate\Support\Facades\Facade;

class ElasticsearchFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'elasticsearch';
    }
}
