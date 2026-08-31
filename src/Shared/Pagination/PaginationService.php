<?php

namespace App\Shared\Pagination;

final class PaginationService
{
    public function create(
        int $page,
        int $limit,
        int $totalItems,
        int $leadingSlots = 0,
    ): Pagination {
        return new Pagination($page, $limit, $totalItems, $leadingSlots);
    }
}
