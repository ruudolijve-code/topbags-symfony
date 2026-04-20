<?php

namespace App\Shared\Pagination;

final class PaginationService
{
    public function create(
        int $page,
        int $limit,
        int $totalItems
    ): Pagination {
        return new Pagination($page, $limit, $totalItems);
    }
}