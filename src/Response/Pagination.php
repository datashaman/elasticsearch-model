<?php namespace Datashaman\Elasticsearch\Model\Response;

use Eloquent;
use Illuminate\Contracts\Pagination\Presenter;
use Illuminate\Pagination\BootstrapThreePresenter;
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
        $this->paginate([ 'page' => $num, 'perPage' => $this->perPage() ]);
        return $this;
    }

    public function perPage($num=null)
    {
        if (is_null($num)) {
            return array_get($this->search->definition, 'size', $this->defaultPerPage());
        }

        $this->paginate([ 'page' => $this->currentPage(), 'perPage' => $num ]);
        return $this;
    }

    public function from()
    {
        $from = array_get($this->search->definition, 'from');
        return $from;
    }

    public function currentPage()
    {
        $from = $this->from();
        $perPage = $this->perPage();

        if (!is_null($from) && !empty($perPage)) {
            return $from / $perPage + 1;
        }
    }

    public function lastPage()
    {
        $from = $this->from();
        $perPage = $this->perPage();

        if (!is_null($from) && !empty($perPage)) {
            return ceil($this->total() / $perPage);
        }
    }

    public function toArray()
    {
        return $this->results->toArray();

        /*
        $from = $this->from();
        $total = $this->total();
        $perPage = $this->perPage();

        return [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $this->currentPage(),
            'last_page' => $this->lastPage(),
            'from' => $from,
            'to' => min(($from + 1) * $perPage - 1, $total),
            'data' => $this->results->toArray(),
        ];
         */
    }

    public function render(Presenter $presenter = null)
    {
        if (is_null($presenter) && isset(static::$presenterResolver)) {
            $presenter = call_user_func(static::$presenterResolver, $this);
        }
        $presenter = $presenter ?: new BootstrapThreePresenter($this->paginator);
        return $presenter->render();
    }
}
