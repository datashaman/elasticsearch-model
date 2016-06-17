<?php namespace Datashaman\ElasticModel\Response;

use ArrayAccess;
use Datashaman\ElasticModel\Traits\ArrayDelegate;


class Records extends Base implements ArrayAccess
{
    use ArrayDelegate;

    protected $_arrayDelegate = 'records';

    public function __construct($class, $response, $options=[])
    {
        parent::__construct($class, $response);
        $this->options = $options;
    }

    public function __get($name)
    {
        switch ($name) {
        case 'ids':
            return array_map(function ($hit) {
                return $hit['_id'];
            }, $this->response->response['hits']['hits']);
        case 'results':
            return $this->response->results;
        case 'records':
            /** TODO: Abstract this so more than Eloquent can be used */
            $records = $this->class::whereIn('id', $this->ids)->get();

            $ordered = [];

            foreach ($this->ids as $id) {
                foreach ($records as $index => $record) {
                    if ($record->id == $id) {
                        $ordered[] = $record;
                        array_forget($records, $index);
                    }
                }
            }

            return $ordered;
        }
    }
}
