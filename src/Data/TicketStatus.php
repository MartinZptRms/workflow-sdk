<?php
/**
 * @author Martín Isaí Zapata Ramos
 * @email  martin.isai@zapataramos.com
 */

namespace Workflow\SDK\Data;

/**
 * Current workflow state of a ticket.
 */
final class TicketStatus
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $key,
        public readonly string $color,
        public readonly bool   $isInitial,
        public readonly bool   $isClosed,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:        $data['id']         ?? '',
            name:      $data['name']       ?? '',
            key:       $data['key']        ?? '',
            color:     $data['color']      ?? '#000000',
            isInitial: (bool) ($data['is_initial'] ?? false),
            isClosed:  (bool) ($data['is_closed']  ?? false),
        );
    }
}
