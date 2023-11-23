<?php

namespace Artbycode\QueryTable;

use Illuminate\Http\Request;


class QRequest
{

    private $request;


    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    private function _getRequest($key): mixed
    {
        return $this->request->get($key);
    }

    public function filter($key): ?string
    {
        $filter = $this->_getRequest('filter');
        if (is_null($filter)) {
            return null;
        }

        if(!array_key_exists($key, $filter)) {
            return null;
        }

        return $filter[$key];
    }

    public function sortable(): array
    {
        $sort = $this->_getRequest('sort');
        if (is_null($sort)) {
            return [];
        }
        $sort = explode(',', $sort);

        $listOrder = [];
        foreach ($sort as $value) {
            $value = explode(':', $value);
            $orderBy = '';
            $columnName = $value[0];

            if (count($value) == 2) {
                $orderBy = $value[1];
            }

            $orderBy = strtolower($orderBy);
            if (in_array($orderBy, ['asc', 'desc'])) {
                $orderBy = $orderBy;
            } else {
                $orderBy = 'asc';
            }

            $listOrder[$columnName] = $orderBy;
        }
        return $listOrder;
    }

    public function perPage(): ?int
    {
        $perPage = $this->_getRequest('limit');
        return $perPage;
    }

    public function search(): ?string {
        $search = $this->_getRequest('q');
        return $search;
    }

}
