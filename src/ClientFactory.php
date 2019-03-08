<?php

namespace Datashaman\Elasticsearch\Model;

use Elasticsearch\ClientBuilder;

class ClientFactory
{
    public function make($app)
    {
        $config = array_get(
            $app['config'],
            'elasticsearch',
            [
                'hosts' => '127.0.0.1:9200',
            ]
        );

        return ClientBuilder::fromConfig($config, true);
    }
}
