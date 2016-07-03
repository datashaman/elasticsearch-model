<?php namespace Datashaman\ElasticModel\Response;

use Illuminate\Pagination\LengthAwarePaginator;

trait Pagination
{
    public function paginate($options)
    {
        $pageName = array_pull($options, 'pageName', 'page');
        $currentPage = max((int) array_pull($options, $pageName), 1);
        $perPage = (int) array_pull($options, 'perPage', $this->defaultPerPage());

        $args = [
            'size' => $perPage,
            'from' => ($currentPage - 1) * $perPage,
        ];

        $this->search()->update($args);

        // $results = $this->results->results->all();
        // $paginator = new LengthAwarePaginator($results, $this->results->total, $perPage, $currentPage, $options);

        return $this;
    }

    public function defaultPerPage()
    {
        $class = $this->class;

        if (isset($class::$perPage)) {
            return $class::$perPage;
        }

        return 15;
    }

    public function page($num)
    {
        $this->paginate([ 'page' => $num, 'perPage' => $this->perPage() ]);
        return $this;
    }

    public function perPage($num=null)
    {
        if (is_null($num)) {
            return array_get($this->search()->definition, 'size', $this->defaultPerPage());
        }

        $this->paginate([ 'page' => $this->currentPage(), 'perPage' => $num ]);
        return $this;
    }

    public function currentPage()
    {
        $from = array_get($this->search()->definition, 'from');
        $perPage = $this->perPage();

        if (!is_null($from) && $perPage) {
            return $from / $perPage + 1;
        }
    }

    public function toArray()
    {
        return $this->results()->results->all();
    }
}
