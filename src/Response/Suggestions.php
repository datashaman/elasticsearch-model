<?php namespace Datashaman\ElasticModel\Response;

use ArrayObject;

class Suggestions extends ArrayObject
{
    public function __get($name) {
        switch ($name) {
        case 'terms':
            $array = $this->getArrayCopy();
            return array_unique(array_map(function ($v) {
                return $v['text'];
            }, array_flatten(array_map(function ($k, $v) {
                return head($v)['options'];
            }, array_keys($array), $array))));
        }
    }
}
