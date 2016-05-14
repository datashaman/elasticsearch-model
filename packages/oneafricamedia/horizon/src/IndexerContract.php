<?php

namespace Oneafricamedia\Horizon;

use App\Listing;

interface IndexerContract
{
    public function get($id);
    public function index(Listing $listing);
    public function delete($id);
    public function search();
}
