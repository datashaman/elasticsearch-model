<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    protected $hidden = [
        'type',
    ];

    public function getPropertiesAttribute()
    {
        return json_decode(array_get($this->attributes, 'properties', '{}'));
    }

    public function setPropertiesAttribute($value)
    {
        $this->attributes['properties'] = json_encode($value, JSON_PRETTY_PRINT);
    }

    public function getTypeAttribute()
    {
        $parser = app('Oneafricamedia\Horizon\ParserContract');
        $type = $parser->parseSchema($this->attributes['type']);
        return $type;
    }

    public function setTypeAttribute($value)
    {
        $this->attributes['type'] = $value;
    }
}
