<?php

namespace App\Shared\Pagination;

final class Pagination
{
    public function __construct(
        private int $page,
        private int $limit,
        private int $totalItems
    ) {
        $this->page = max(1, $page);
        $this->limit = max(1, $limit);
        $this->totalItems = max(0, $totalItems);
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->limit;
    }

    public function getTotalPages(): int
    {
        return max(1, (int) ceil($this->totalItems / $this->limit));
    }

    public function hasPreviousPage(): bool
    {
        return $this->page > 1;
    }

    public function hasNextPage(): bool
    {
        return $this->page < $this->getTotalPages();
    }

    public function getPreviousPage(): int
    {
        return max(1, $this->page - 1);
    }

    public function getNextPage(): int
    {
        return min($this->getTotalPages(), $this->page + 1);
    }

    /**
     * @return int[]
     */
    public function getPages(): array
    {
        return range(1, $this->getTotalPages());
    }
}