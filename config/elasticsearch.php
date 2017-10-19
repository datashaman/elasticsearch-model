<?php

return [
    // Configure env variable with a comma-delimited string of hosts, defaults to localhost:9200.
    // It is expanded here to an array of host definitions.
    'hosts' => explode(',', env('ELASTICSEARCH_HOSTS', 'localhost:9200')),

    // Optionally specify global retries for operations, defaults to number of nodes in cluster.
    // 'retries' => 3,

    // Specify the result class to be used globally. Defaults to Datashaman\ElasticsearchModel\Response\Result.
    // 'resultClass' => '',

    // Use a factory callable for creating the result globally. Accepts hit from Elasticsearch response as the only parameter.
    // 'resultFactory' => [
    //     App\ResultFactory::class,
    //     'createResult'
    // ],
];
