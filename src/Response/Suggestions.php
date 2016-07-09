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

    public function terms()
    {
        $terms = collect($this->input)
            ->map(function ($value) {
                return head($value)['options'];
            })
            ->reduce(function ($carry, $item) {
                collect(is_array($item) ? $item : [$item])->each(function ($item) use ($carry) {
                    $carry->push($item);
                });
                return $carry;
            }, collect())
            ->map(function ($value) {
                return $value['text'];
            })
            ->unique();

        return $terms;
    }
}
