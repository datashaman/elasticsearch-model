<?php

namespace Oneafricamedia\Horizon;

use App\Listing;
use Elasticsearch\ClientBuilder;

class Indexer implements IndexerContract
{
    protected $client;

    public function __construct()
    {
        $this->client = ClientBuilder::create()->build();
    }

    protected function prepareParams($id, $params=[])
    {
        $params = array_merge([
            'index' => 'listings',
            'type' => 'listing',
            'id' => $id,
            'client' => [
                'future' => 'lazy',
            ],
        ], $params);
        return $params;
    }

    public function get($id)
    {
        $params = $this->prepareParams($id);
        $response = $this->client->get($params);
        return $response;
    }

    public function index(Listing $listing)
    {
        $body = $listing->toArray();
        $params = $this->prepareParams($listing->id, compact('body'));
        $response = $this->client->index($params);
        return $response;
    }

    public function delete($id)
    {
        $params = $this->prepareParams($id);
        $response = $this->client->delete($params);
        return $response;
    }

    public function search()
    {
        $params = [
            'index' => 'listings',
            'type' => 'listing',
            'client' => [
                'future' => 'lazy',
            ],
        ];
        $response = $this->client->search($params);
        return $response;
    }
}
