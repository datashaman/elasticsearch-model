<?php namespace Datashaman\ElasticModel\Response;

use ArrayAccess;
use Datashaman\ElasticModel\ArrayDelegate;

class Suggestions implements ArrayAccess
{
    use ArrayDelegate;

    protected static $arrayDelegate = 'input';

    public function __construct($input=[])
    {
        $this->input = $input;
    }

    public function __get($name) {
        switch ($name) {
        case 'terms':
            $input = $this->input;

            $flattened = array_reduce(array_map(function ($value) {
                return head($value)['options'];
            }, $input), function ($carry, $item) {
                $carry += is_array($item) ? $item : [$item];
                return $carry;
            }, []);

            $terms = array_unique(array_map(function ($value) {
                return $value['text'];
            }, $flattened));

            return $terms;
        }
    }
}
