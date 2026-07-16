<?php
/**
 * @author Martín Isaí Zapata Ramos
 * @email  martin.isai@zapataramos.com
 */

namespace Workflow\SDK\Tests\Unit\Resources;

use PHPUnit\Framework\TestCase;
use Workflow\SDK\Data\Ticket;
use Workflow\SDK\Data\TicketList;
use Workflow\SDK\Tests\Support\Fixtures;
use Workflow\SDK\Tests\Support\MockTransport;
use Workflow\SDK\WorkflowClient;

class TicketResourceTest extends TestCase
{
    private function makeClient(array $responses): array
    {
        $transport = new MockTransport($responses);
        $client    = new WorkflowClient('test-key', 'https://example.com', transport: $transport);

        return [$client, $transport];
    }

    // ── create ────────────────────────────────────────────────

    public function test_create_returns_ticket(): void
    {
        [$client] = $this->makeClient([
            [201, Fixtures::ticketCreatedResponse()],
        ]);

        $ticket = $client->tickets->create(['title' => 'Test ticket']);

        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertSame('ticket-uuid-001', $ticket->id);
        $this->assertSame('Test ticket', $ticket->title);
        $this->assertSame('medium', $ticket->priority);
        $this->assertSame('todo', $ticket->status->key);
        $this->assertSame('support', $ticket->type->key);
    }

    public function test_create_sends_all_fields(): void
    {
        [$client, $transport] = $this->makeClient([
            [201, Fixtures::ticketCreatedResponse()],
        ]);

        $client->tickets->create([
            'title'              => 'My ticket',
            'description'        => 'Something went wrong',
            'priority'           => 'high',
            'workflow_id'        => 'wf-uuid',
            'item_type_id'       => 'type-uuid',
            'client_external_id' => 'usr_123',
        ]);

        $body = json_decode($transport->lastRequest()['body'], true);
        $this->assertSame('My ticket', $body['title']);
        $this->assertSame('high', $body['priority']);
        $this->assertSame('wf-uuid', $body['workflow_id']);
        $this->assertSame('usr_123', $body['client_external_id']);
    }

    public function test_create_returns_null_on_422(): void
    {
        [$client] = $this->makeClient([
            [422, Fixtures::validationErrorResponse()],
        ]);

        $this->assertNull($client->tickets->create([]));
    }

    public function test_create_returns_null_on_401(): void
    {
        [$client] = $this->makeClient([
            [401, Fixtures::authErrorResponse()],
        ]);

        $this->assertNull($client->tickets->create(['title' => 'Test']));
    }

    // ── list ─────────────────────────────────────────────────

    public function test_list_returns_ticket_list(): void
    {
        [$client] = $this->makeClient([
            [200, Fixtures::ticketListResponse()],
        ]);

        $list = $client->tickets->list();

        $this->assertInstanceOf(TicketList::class, $list);
        $this->assertCount(1, $list);
        $this->assertFalse($list->hasMore());
    }

    public function test_list_sends_query_params(): void
    {
        [$client, $transport] = $this->makeClient([
            [200, Fixtures::ticketListResponse()],
        ]);

        $client->tickets->list([
            'client_external_id' => 'usr_123',
            'only_open'          => true,
            'per_page'           => 5,
        ]);

        $this->assertStringContainsString('client_external_id=usr_123', $transport->lastRequest()['url']);
        $this->assertStringContainsString('per_page=5', $transport->lastRequest()['url']);
    }

    public function test_list_filters_open_and_closed(): void
    {
        $closed = Fixtures::ticket(['closed_at' => '2026-05-15T12:00:00Z', 'status' => [
            'id' => 'done', 'name' => 'Done', 'key' => 'done',
            'color' => '#10B981', 'is_initial' => false, 'is_closed' => true,
        ]]);

        [$client] = $this->makeClient([
            [200, Fixtures::ticketListResponse([Fixtures::ticket(), $closed])],
        ]);

        $list = $client->tickets->list();

        $this->assertCount(1, $list->open());
        $this->assertCount(1, $list->closed());
    }

    public function test_list_hasmore_reflects_pagination(): void
    {
        [$client] = $this->makeClient([
            [200, Fixtures::ticketListResponse([], ['current_page' => 1, 'last_page' => 3])],
        ]);

        $list = $client->tickets->list();

        $this->assertTrue($list->hasMore());
        $this->assertFalse($list->meta->isLastPage());
    }

    // ── find ─────────────────────────────────────────────────

    public function test_find_returns_ticket(): void
    {
        [$client, $transport] = $this->makeClient([
            [200, ['data' => Fixtures::ticket(['id' => 'specific-uuid']), 'code' => 'ok']],
        ]);

        $ticket = $client->tickets->find('specific-uuid');

        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertSame('specific-uuid', $ticket->id);
        $this->assertStringEndsWith('/specific-uuid', $transport->lastRequest()['url']);
    }

    public function test_find_returns_null_on_404(): void
    {
        [$client] = $this->makeClient([
            [404, ['message' => 'Resource not found.', 'code' => 'not_found']],
        ]);

        $this->assertNull($client->tickets->find('non-existent-uuid'));
    }
}
