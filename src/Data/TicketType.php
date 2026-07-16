<?php
/**
 * @author Martín Isaí Zapata Ramos
 * @email  martin.isai@zapataramos.com
 */

namespace Workflow\SDK\Data;

/**
 * Item type of a ticket (Bug, Feature, Support, etc.).
 */
final class TicketType
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $key,
        public readonly string $color,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:    $data['id']    ?? '',
            name:  $data['name']  ?? '',
            key:   $data['key']   ?? '',
            color: $data['color'] ?? '#000000',
        );
    }
}
