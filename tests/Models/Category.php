<?php namespace Datashaman\Elasticsearch\Model\Tests\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Category extends Eloquent
{
    public function things()
    {
        return $this->hasMany(Thing::class, 'category_id');
    }
}
