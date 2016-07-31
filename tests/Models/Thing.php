<?php

namespace Datashaman\Elasticsearch\Model\Tests\Models;

use Datashaman\Elasticsearch\Model\ElasticsearchModel;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Thing extends Eloquent
{
    use ElasticsearchModel;
    protected static $elasticsearch;

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function perPage()
    {
        return 33;
    }

    public function scopeOnline($query)
    {
        return $query->whereStatus('online');
    }
}
