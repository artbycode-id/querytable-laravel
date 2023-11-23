<?php

namespace Artbycode\QueryTable;


class QColumn
{

    /**
     * @var string
     * */
    public string $key;

    /**
     * @var string
     * */

    public string $display;

    /**
     * @var QTable
     */
    private QTable $qtable;

    /**
     * @var bool
     * */
    private bool $sortable = false;


    /**
     * @var bool
     * */
    private bool $searchable = false;

    /**
     * @var array
     * */

    private bool $hasCustomSearch = false;

    /**
     * @var bool
     * */

    private bool $hide = false;

    /**
     * @var callable $format
     */
    private $funcFormat = [];


    /**
     * Constructor for the class.
     *
     * @return bool Returns true.
     */
    public function __construct(string $key, ?string $display = null)
    {
        if (is_null($display)) {
            $display = ucfirst(str_replace('_', ' ', $key));
        }

        $this->display = $display;
        $this->key = $key;
    }

    public function withTable(QTable $qtable): self
    {
        $this->qtable = $qtable;
        return $this;
    }

    /**
     * @param array $format 
     * @return array
     * */

    public function format(array|string $format): self
    {
        if (!is_array($format)) {
            $format = [$format];
        }
        $this->funcFormat = $format;
        return $this;
    }


    public function sortable(bool $sortable = true): self
    {
        $this->sortable = $sortable;
        return $this;
    }

    public function hide(bool $hide = true): self
    {
        $this->hide = $hide;
        return $this;
    }

    public function isHide(): bool
    {
        return $this->hide;
    }


    public function searchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;
        return $this;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function customSearch(callable $callback): self
    {
        $callback($this->qtable->getQuery(), $this->key);
        $this->hasCustomSearch = true;
        return $this;
    }

    private function _getRequest($key): ?string
    {
        return $this->qtable->getRequest()->filter($key);
    }

    public function applySearchable(): void
    {

        $request = $this->_getRequest($this->key);

        if ($this->hasCustomSearch || !$this->searchable) {
            return;
        }

        if (is_null($request)) {
            return;
        }

        $this->qtable->getQuery()->where($this->key, 'LIKE', '%' . $request . '%');
    }


    public function apply(): void
    {
        if ($this->searchable && $this->_getRequest($this->key)) {
            $this->applySearchable();
        }
    }


    public function get(): array
    {
        return [
            'key' => $this->key,
            'display' => $this->display,
            'sortable' => $this->sortable,
            'searchable' => $this->searchable,
            'hide' => $this->hide,
        ];
    }

    public function getDisplay($row, $value): mixed
    {
        if (empty($this->funcFormat)) {
            return $value;
        }

        foreach ($this->funcFormat as $format) {
            if (is_string($format)) {
                $value = QFormat::make($value, $format)->getFormated();
                continue;
            }
            $value = call_user_func($format, $row, $value);
        }

        return $value;
    }
}
