<?php

namespace Datashaman\Elasticsearch\Model;

trait HasProperties
{
    public function getPropertiesAttribute()
    {
        $properties = array_get($this->attributes, 'properties', '{}');
        if (empty($properties)) {
            $properties = '{}';
        }

        return json_decode($properties, true);
    }

    public function setPropertiesAttribute($value)
    {
        $this->attributes['properties'] = json_encode($value, JSON_PRETTY_PRINT);
    }
}
