<?php
/**
 * @author Martín Isaí Zapata Ramos
 * @email  martin.isai@zapataramos.com
 */

namespace Workflow\SDK\Data;

/**
 * Paginated list of tickets.
 *
 * Implements Countable and IteratorAggregate so it can be used
 * directly in foreach loops and count() calls.
 *
 * @implements \IteratorAggregate<int, Ticket>
 */
final class TicketList implements \Countable, \IteratorAggregate
{
    /** @param Ticket[] $items */
    public function __construct(
        public readonly array          $items,
        public readonly PaginationMeta $meta,
    ) {}

    public static function fromArray(array $response): self
    {
        return new self(
            items: array_map(
                fn(array $t) => Ticket::fromArray($t),
                $response['data'] ?? []
            ),
            meta: PaginationMeta::fromArray($response['meta'] ?? []),
        );
    }

    public function count(): int
    {
        return count($this->items);
    }

    /** @return \ArrayIterator<int, Ticket> */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function hasMore(): bool
    {
        return $this->meta->hasNextPage();
    }

    /** @return Ticket[] */
    public function open(): array
    {
        return array_values(array_filter($this->items, fn(Ticket $t) => $t->isOpen()));
    }

    /** @return Ticket[] */
    public function closed(): array
    {
        return array_values(array_filter($this->items, fn(Ticket $t) => $t->isClosed()));
    }
}
