<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    public function getPropertiesAttribute()
    {
        return json_decode(array_get($this->attributes, 'properties', '{}'));
    }

    public function setPropertiesAttribute($value)
    {
        $this->attributes['properties'] = json_encode($value, JSON_PRETTY_PRINT);
    }
}
