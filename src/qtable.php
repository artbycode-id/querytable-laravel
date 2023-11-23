<?php

namespace Artbycode\QueryTable;

use \Illuminate\Database\Eloquent\Builder;
use \Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;


class QTable
{

    private Builder $query;

    private array $columns = [];

    private array $listVisibleColumns = [];

    private array $listHideColumns = [];

    private array $displayColumns = [];

    private QRequest $request;

    private array $listDefaultSortColumns = [];

    private int $limitPerPage = 15;

    /**
     * Constructor for the class.
     *
     * @return bool Returns true.
     */
    public function __construct()
    {
        $this->request = new QRequest(request());
    }

    public function visibleColumns(array $columns): self
    {
        $this->listVisibleColumns = $columns;
        return $this;
    }

    public function hideColumns(array $columns): self
    {
        $this->listHideColumns = $columns;
        return $this;
    }


    // callback function column
    public function column(string $key, ?string $display = null): QColumn
    {
        $column =  new QColumn($key, $display);
        $column->withTable($this);
        $this->columns[$key] = $column;
        return $column;
    }


    public static function make(Builder $query): QTable
    {
        return (new QTable())->withQueryBuilder($query);
    }

    public static function model(Model $model): QTable
    {
        return (new QTable())->withModel($model);
    }

    public function withQueryBuilder(Builder $query): self
    {
        $this->query = $query;
        return $this;
    }

    public function withModel(Model $model): self
    {
        return $this->withQueryBuilder($model->newQuery());
    }

    public function withQuery(callable $query): self
    {
        $query($this->query);
        return $this;
    }

    public function getQuery(): Builder
    {
        return $this->query;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function defaultSortColumns(array $defaults): self
    {
        foreach ($defaults as $value) {
            $split = explode(':', $value);
            if (count($split) > 1) {
                $this->listDefaultSortColumns[$split[0]] = $split[1];
            }
            $this->listDefaultSortColumns[$value] = 'asc';
        }
        return $this;
    }


    public function applySortableColumns(): void
    {
        $sortRequest = $this->request->sortable();

        foreach ($sortRequest as $key => $value) {
            // if key not exist in columns, skip
            if (!array_key_exists($key, $this->columns)) {
                continue;
            }

            // if key column not sortable
            if (!$this->columns[$key]->isSortable()) {
                continue;
            }


            $this->query->orderBy($key, $value);
        }

        foreach ($this->listDefaultSortColumns as $key => $value) {

            // if key not exist in columns, skip
            if (!array_key_exists($key, $this->columns)) {
                continue;
            }

            // if key exist in sortRequest, skip
            if (array_key_exists($key, $sortRequest)) {
                continue;
            }
            $this->query->orderBy($key, $value);
        }
    }

    private function applyHideVisibleColumns()
    {
        if (!empty($this->listVisibleColumns) && !empty($this->listHideColumns)) {
            throw new \Exception('Cannot use visibleColumns and hideColumns method at the same time');
        }

        foreach ($this->listVisibleColumns as $key) {
            $this->columns[$key] = $this->columns[$key]->hide(false);
        }

        foreach ($this->listHideColumns as $key) {
            $this->columns[$key] = $this->columns[$key]->hide(true);
        }
    }

    private function applySearchColumns(): void
    {
        $paramSearch = $this->request->search();
        if (empty($paramSearch)) {
            return;
        }


        $columnSearch = [];
        foreach ($this->columns as $key => $value) {
            // if key column not searchable
            if (!$value->isSearchable()) {
                continue;
            }
            $columnSearch[] = $key;
        }


        $this->query->where(function ($query) use ($columnSearch, $paramSearch) {
            foreach ($columnSearch as $value) {
                $query->orWhere($value, 'like', '%' . $paramSearch . '%');
            }
        });
    }

    private function applyColumns(): void
    {
        foreach ($this->columns as $column) {
            $column->apply();
        }
        $this->applyHideVisibleColumns();
        $this->applySearchColumns();
        $this->applySortableColumns();
        $this->applyDisplayColumns();
    }

    public function getRequest(): QRequest
    {
        return $this->request;
    }

    public function build(): void
    {
        $this->applyColumns();
    }

    public function applyDisplayColumns(): void
    {
        foreach ($this->columns as $key => $column) {
            $this->displayColumns[$key] = $column->get();
        }
    }


    private function buildResults(\Illuminate\Pagination\LengthAwarePaginator $paginate): array
    {
        $columns = $this->getVisibleColumns();
        $citems = $paginate->getCollection()->map(function ($item) use ($columns) {
            $items = $item->only($columns);
            foreach ($items as $key => $value) {
                $items[$key] = $this->columns[$key]->getDisplay($item, $value);
            }
            return $items;
        });

        return [
            'meta' => [
                'per_page' => $paginate->perPage(),
                'current_page' => $paginate->currentPage(),
                'last_page' => $paginate->lastPage(),
                'from' => $paginate->firstItem(),
                'to' => $paginate->lastItem(),
                'sort' => $this->request->sortable(),
                'q' => $this->request->search(),
            ],
            'columns' => $this->displayColumns,
            'rows' => $citems,
        ];
    }

    private function getVisibleColumns(): array
    {
        $columns = array_filter($this->columns, function ($column) {
            return !$column->isHide();
        });
        return array_keys($columns);
    }


    public function json(): array
    {
        $this->build();
        return $this->buildResults($this->query->paginate(
            $this->request->perPage() ?? $this->limitPerPage,
        ));
    }

    public function __toString()
    {
        return json_encode($this->json());
    }

    public function __call($name, $arguments)
    {
        if (QFormat::inList($name)) {
            // have 2 arguments
            if (count($arguments) == 2) {
                return $this->column($arguments[0], $arguments[1])->format($name);
            }
            // have 1 arguments
            if (count($arguments) == 1) {
                return $this->column($arguments[0])->format($name);
            }
            throw new \Exception('Arguments must be 1 or 2');
        }

        throw new \Exception('Method ' . $name . ' not found');
    }
}
