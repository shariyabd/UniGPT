<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

/**
 * Paginates an already-built in-memory collection or array (typically the
 * presented output of a domain service) into a Laravel paginator, so list pages
 * fed by services get real server-side paging without altering the shared
 * service methods that the test suite asserts on as collections.
 */
trait PaginatesCollections
{
    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    protected function paginateCollection(
        Collection|array $items,
        int $perPage = 25,
        string $pageName = 'page',
    ): LengthAwarePaginatorContract {
        $collection = collect($items)->values();
        $page = Paginator::resolveCurrentPage($pageName);

        $paginator = new LengthAwarePaginator(
            $collection->forPage($page, $perPage)->values(),
            $collection->count(),
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ],
        );

        return $paginator->withQueryString();
    }
}
