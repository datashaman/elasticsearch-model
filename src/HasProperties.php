<?php namespace Datashaman\ElasticModel;

trait HasProperties
{
    public function getPropertiesAttribute()
    {
        return json_decode(array_get($this->attributes, 'properties', '{}'), true);
    }

    public function setPropertiesAttribute($value)
    {
        $this->attributes['properties'] = json_encode($value, JSON_PRETTY_PRINT);
    }
}
