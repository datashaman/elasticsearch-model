<?php

namespace Datashaman\Elasticsearch\Model\Response;

use Eloquent;

trait Pagination
{
    public function paginate($options)
    {
        $this->attributes = collect();

        $pageName = array_pull($options, 'pageName', 'page');
        $currentPage = max((int) array_pull($options, $pageName), 1);
        $perPage = (int) array_pull($options, 'perPage', $this->defaultPerPage());

        $args = [
            'size' => $perPage,
            'from' => ($currentPage - 1) * $perPage,
        ];

        $this->search->update($args);

        return $this;
    }

    public function defaultPerPage()
    {
        $class = $this->search->class;

        if (isset($class::$perPage)) {
            return $class::$perPage;
        }

        $instance = new $class;

        if ($instance instanceof Eloquent) {
            return $instance->getPerPage();
        }

        return 15;
    }

    public function page($num)
    {
        $this->paginate(['page' => $num, 'perPage' => $this->perPage()]);

        return $this;
    }

    public function perPage($num = null)
    {
        if (is_null($num)) {
            return array_get($this->search->definition, 'size', $this->defaultPerPage());
        }

        $this->paginate(['page' => $this->currentPage(), 'perPage' => $num]);

        return $this;
    }

    public function currentPage()
    {
        $from = $this->from();
        $perPage = $this->perPage();

        if (! is_null($from) && ! empty($perPage)) {
            return $from / $perPage + 1;
        }
    }

    public function lastPage()
    {
        $from = $this->from();
        $perPage = $this->perPage();

        if (! is_null($from) && ! empty($perPage)) {
            return (int) ceil($this->total() / $perPage);
        }
    }

    public function toArray()
    {
        return $this->paginator()->toArray();
    }
}
