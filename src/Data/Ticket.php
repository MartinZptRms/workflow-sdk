<?php
/**
 * @author Martín Isaí Zapata Ramos
 * @email  martin.isai@zapataramos.com
 */

namespace Workflow\SDK\Data;

/**
 * Represents a ticket returned by the Workflow API.
 */
final class Ticket
{
    public function __construct(
        public readonly string       $id,
        public readonly string       $title,
        public readonly ?string      $description,
        public readonly string       $priority,
        public readonly string       $source,
        public readonly TicketStatus $status,
        public readonly TicketType   $type,
        public readonly ?string      $workflowId,
        public readonly ?string      $clientId,
        public readonly ?string      $createdBy,
        public readonly ?string      $closedAt,
        public readonly string       $createdAt,
        public readonly string       $updatedAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:          $data['id'],
            title:       $data['title'],
            description: $data['description'] ?? null,
            priority:    $data['priority'],
            source:      $data['source'],
            status:      TicketStatus::fromArray($data['status'] ?? []),
            type:        TicketType::fromArray($data['type']     ?? []),
            workflowId:  $data['workflow_id'] ?? null,
            clientId:    $data['client_id']   ?? null,
            createdBy:   $data['created_by']  ?? null,
            closedAt:    $data['closed_at']   ?? null,
            createdAt:   $data['created_at'],
            updatedAt:   $data['updated_at'],
        );
    }

    public function isOpen(): bool
    {
        return $this->closedAt === null && ! $this->status->isClosed;
    }

    public function isClosed(): bool
    {
        return ! $this->isOpen();
    }

    public function isHighPriority(): bool
    {
        return in_array($this->priority, ['high', 'critical'], strict: true);
    }
}
