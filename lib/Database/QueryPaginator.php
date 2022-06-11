<?php

namespace SoftwarePunt\Instarecord\Database;

class QueryPaginator
{
    const DEFAULT_PAGE_SIZE = 32;

    /**
     * @var Query
     */
    protected $baseQuery;

    /**
     * @var int|null
     */
    protected $totalItemCount;

    /**
     * @var int
     */
    protected $pageSize;

    /**
     * @var int
     */
    protected $pageIndex;

    /**
     * @var array|int[]
     */
    protected $itemCountOnPage;

    /**
     * QueryPaginator constructor.
     *
     * @param Query|ModelQuery $baseQuery
     */
    public function __construct(Query $baseQuery)
    {
        // Clone the query, without any values for limit or offset
        $this->baseQuery = $baseQuery;
        $this->baseQuery = $this->createDerivedQuery()
            ->limit(null)
            ->offset(null);

        $this->totalItemCount = null;
        $this->itemCountOnPage = [];
        $this->pageIndex = 0;

        $this->setQueryPageSize(self::DEFAULT_PAGE_SIZE);
    }

    /**
     * Clones the base query, and returns that instance.
     *
     * @return Query
     */
    protected function createDerivedQuery(): Query
    {
        return (clone $this->baseQuery);
    }

    /**
     * Sets the page size.
     *
     * @param int $pageSize Items per page.
     * @return QueryPaginator $this
     */
    public function setQueryPageSize(int $pageSize): QueryPaginator
    {
        $this->pageSize = $pageSize;
        return $this;
    }

    /**
     * Sets the current page number / index.
     *
     * @param int $pageIndex
     * @return QueryPaginator
     */
    public function setPageIndex(int $pageIndex): QueryPaginator
    {
        $this->pageIndex = $pageIndex;
        return $this;
    }

    /**
     * Gets the total item count.
     *
     * @param bool $distinct If true, apply a DISTINCT clause
     * @return int Total items without pagination.
     */
    public function getTotalItemCount(string $column = '*', bool $distinct = false): int
    {
        if ($this->totalItemCount === null) {
            $this->totalItemCount = intval($this->createDerivedQuery()
                ->count($distinct ? "DISTINCT {$column}" : $column)
                ->orderBy(null)
                ->querySingleValue());
        }
        return $this->totalItemCount;
    }

    /**
     * Gets the total item count on the current page.
     *
     * @return int
     */
    public function getItemCountOnPage(): int
    {
        if (!$this->getIsValidPage()) {
            // This is not a valid page
            return 0;
        }

        if ($this->getIsLastPage()) {
            // This is the last page, so it's the remainder of items after subtracting
            $totalItemCount = $this->getTotalItemCount();
            return $totalItemCount - ($this->pageIndex * $this->getLimit());
        }

        // This is not the last page, so must be a full page
        return $this->getLimit();
    }

    /**
     * Gets the total number of pages, based on the total amount of items and current page size.
     *
     * @return int The upper page limit (rounded up).
     *@see setQueryPageSize
     */
    public function getPageCount(): int
    {
        $itemCount = $this->getTotalItemCount();

        if ($itemCount === 0) {
            return 0;
        }

        return ceil($itemCount / $this->pageSize);
    }

    /**
     * Gets whether the currently set page number is valid:
     * The current page index must be 0 or greater, and not exceed the upper page limit.
     *
     * NB: If there are no results, this function will still return TRUE for the first page (index zero).
     *
     * @return bool
     * @see getPageCount
     * @see setQueryPageSize
     */
    public function getIsValidPage(): bool
    {
        $pageCount = $this->getPageCount();

        if ($pageCount === 0 && $this->pageIndex === 0) {
            // If there are no results, but this is the first page,.
            return true;
        }

        return $this->pageIndex >= 0 && $this->pageIndex <= ($pageCount - 1);
    }

    /**
     * Gets whether the current page is the first (index zero).
     *
     * @return bool
     */
    public function getIsFirstPage(): bool
    {
        return $this->pageIndex === 0;
    }

    /**
     * Gets whether the current page is the last.
     *
     * @return bool
     */
    public function getIsLastPage(): bool
    {
        $pageCount = $this->getPageCount();

        if ($pageCount === 0 && $this->pageIndex === 0) {
            return true;
        }

        return $this->pageIndex === ($pageCount - 1);
    }

    /**
     * Gets the offset based on the current page and page size.
     *
     * @return int
     * @see setQueryPageSize
     * @see setPageIndex
     */
    public function getOffset(): int
    {
        return $this->pageIndex * $this->pageSize;
    }

    /**
     * Gets the current page size (limit).
     *
     * @return int
     * @see setQueryPageSize
     */
    public function getQueryPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * Gets the current page index.
     *
     * @return int
     *@see setPageIndex
     */
    public function getPageIndex(): int
    {
        return $this->pageIndex;
    }

    /**
     * Gets the current page size (limit).
     * Alias for getPageSize().
     *
     * @return int
     * @see setQueryPageSize
     */
    public function getLimit(): int
    {
        return $this->getQueryPageSize();
    }

    /**
     * Creates and returns a modified Query with the limit and offset from this paginator.
     *
     * @return Query|ModelQuery
     */
    public function getPaginatedQuery(): Query
    {
        return $this->createDerivedQuery()
            ->limit($this->getLimit())
            ->offset($this->getOffset());
    }
}