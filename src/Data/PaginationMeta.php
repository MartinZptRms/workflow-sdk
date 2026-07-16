<?php
/**
 * @author Martín Isaí Zapata Ramos
 * @email  martin.isai@zapataramos.com
 */

namespace Workflow\SDK\Data;

/**
 * Pagination metadata returned by list endpoints.
 */
final class PaginationMeta
{
    public function __construct(
        public readonly int  $currentPage,
        public readonly int  $lastPage,
        public readonly int  $perPage,
        public readonly int  $total,
        public readonly ?int $from,
        public readonly ?int $to,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            currentPage: (int) ($data['current_page'] ?? 1),
            lastPage:    (int) ($data['last_page']    ?? 1),
            perPage:     (int) ($data['per_page']     ?? 15),
            total:       (int) ($data['total']        ?? 0),
            from:        isset($data['from']) ? (int) $data['from'] : null,
            to:          isset($data['to'])   ? (int) $data['to']   : null,
        );
    }

    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->lastPage;
    }

    public function isLastPage(): bool
    {
        return $this->currentPage >= $this->lastPage;
    }
}
