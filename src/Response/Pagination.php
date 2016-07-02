<?php namespace Datashaman\ElasticModel\Response;

use Illuminate\Pagination\LengthAwarePaginator;

trait Pagination
{
    public function paginate($options)
    {
        $pageName = array_pull($options, 'pageName', 'page');
        $currentPage = max((int) array_pull($options, $pageName), 1);
        $perPage = (int) array_pull($options, 'perPage', $this->defaultPerPage());

        $this->search->update([
            'size' => $perPage,
            'from' => ($currentPage - 1) * $perPage,
        ]);

        $results = $this->results->results->all();
        $paginator = new LengthAwarePaginator($results, $this->results->total, $perPage, $currentPage, $options);
        return $paginator;
    }

    public function defaultPerPage()
    {
        $class = $this->class;
        if (isset($class::$defaultPerPage)) {
            return $class::$defaultPerPage;
        } else {
            return 15;
        }
    }

    public function offset()
    {
        return $this->search->definition['from'];
    }
}
