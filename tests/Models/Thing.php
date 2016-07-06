<?php namespace Datashaman\ElasticModel\Tests\Models;

use Datashaman\ElasticModel\ElasticModel;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Thing extends Eloquent
{
    use ElasticModel;
    protected static $elasticsearch;

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function perPage()
    {
        return 33;
    }
}
