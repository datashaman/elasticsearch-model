<?php namespace Datashaman\ElasticModel\Tests\Models;

use Datashaman\ElasticModel\Traits\ElasticModel;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Thing extends Eloquent
{
    use ElasticModel;

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
