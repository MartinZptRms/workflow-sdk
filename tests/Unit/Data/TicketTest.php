<?php
/**
 * @author Martín Isaí Zapata Ramos
 * @email  martin.isai@zapataramos.com
 */

namespace Workflow\SDK\Tests\Unit\Data;

use PHPUnit\Framework\TestCase;
use Workflow\SDK\Data\Ticket;
use Workflow\SDK\Tests\Support\Fixtures;

class TicketTest extends TestCase
{
    public function test_from_array_maps_all_fields(): void
    {
        $ticket = Ticket::fromArray(Fixtures::ticket());

        $this->assertSame('ticket-uuid-001', $ticket->id);
        $this->assertSame('Test ticket', $ticket->title);
        $this->assertSame('medium', $ticket->priority);
        $this->assertSame('api', $ticket->source);
        $this->assertNull($ticket->clientId);
        $this->assertNull($ticket->closedAt);
        $this->assertSame('todo', $ticket->status->key);
        $this->assertTrue($ticket->status->isInitial);
        $this->assertFalse($ticket->status->isClosed);
        $this->assertSame('support', $ticket->type->key);
    }

    public function test_is_open_when_not_closed(): void
    {
        $ticket = Ticket::fromArray(Fixtures::ticket(['closed_at' => null]));

        $this->assertTrue($ticket->isOpen());
        $this->assertFalse($ticket->isClosed());
    }

    public function test_is_closed_when_closed_at_set(): void
    {
        $ticket = Ticket::fromArray(Fixtures::ticket([
            'closed_at' => '2026-05-15T12:00:00Z',
            'status'    => [
                'id' => 's', 'name' => 'Done', 'key' => 'done',
                'color' => '#10B981', 'is_initial' => false, 'is_closed' => true,
            ],
        ]));

        $this->assertTrue($ticket->isClosed());
        $this->assertFalse($ticket->isOpen());
    }

    public function test_is_high_priority_for_high_and_critical(): void
    {
        $high     = Ticket::fromArray(Fixtures::ticket(['priority' => 'high']));
        $critical = Ticket::fromArray(Fixtures::ticket(['priority' => 'critical']));
        $medium   = Ticket::fromArray(Fixtures::ticket(['priority' => 'medium']));

        $this->assertTrue($high->isHighPriority());
        $this->assertTrue($critical->isHighPriority());
        $this->assertFalse($medium->isHighPriority());
    }
}
