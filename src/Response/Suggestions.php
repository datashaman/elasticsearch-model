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

            $flattened = array_reduce(array_map(function ($k, $v) {
                return head($v)['options'];
            }, array_keys($input), $input), function ($carry, $item) {
                if (is_array($item)) {
                    $carry += $item;
                } else {
                    $carry[] = $item;
                }
                return $carry;
            }, []);

            $terms = array_unique(array_map(function ($v) {
                return $v['text'];
            }, $flattened));

            return $terms;
        }
    }
}
