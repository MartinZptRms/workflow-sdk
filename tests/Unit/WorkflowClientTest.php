<?php
/**
 * @author Martín Isaí Zapata Ramos
 * @email  martin.isai@zapataramos.com
 */

namespace Workflow\SDK\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Workflow\SDK\Data\Ticket;
use Workflow\SDK\Tests\Support\Fixtures;
use Workflow\SDK\Tests\Support\MockTransport;
use Workflow\SDK\WorkflowClient;

class WorkflowClientTest extends TestCase
{
    private function makeClient(array $responses = []): array
    {
        $transport = new MockTransport($responses);
        $client    = new WorkflowClient('test-key', 'https://example.com', transport: $transport);

        return [$client, $transport];
    }

    public function test_ticket_shortcut_delegates_to_tickets_create(): void
    {
        [$client, $transport] = $this->makeClient([
            [201, Fixtures::ticketCreatedResponse(['title' => 'My ticket'])],
        ]);

        $ticket = $client->ticket(['title' => 'My ticket', 'priority' => 'high']);

        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertSame('My ticket', $ticket->title);
        $transport->assertRequestCount(1);
        $transport->assertLastRequestBodyContains('title', 'My ticket');
        $transport->assertLastRequestBodyContains('priority', 'high');
    }

    public function test_report_sends_critical_ticket_with_exception_data(): void
    {
        [$client, $transport] = $this->makeClient([
            [201, Fixtures::ticketCreatedResponse(['priority' => 'critical'])],
        ]);

        $exception = new \RuntimeException('Something broke');
        $ticket    = $client->report($exception, ['user_id' => 42]);

        $this->assertInstanceOf(Ticket::class, $ticket);
        $transport->assertRequestCount(1);
        $transport->assertLastRequestBodyContains('priority', 'critical');

        $body = json_decode($transport->lastRequest()['body'], true);
        $this->assertStringContainsString('RuntimeException', $body['title']);
        $this->assertStringContainsString('Something broke', $body['title']);
        $this->assertStringContainsString('Stack Trace', $body['description']);
        $this->assertStringContainsString('"user_id": 42', $body['description']);
    }

    public function test_report_includes_workflow_id_when_provided(): void
    {
        [$client, $transport] = $this->makeClient([
            [201, Fixtures::ticketCreatedResponse()],
        ]);

        $client->report(new \RuntimeException('Oops'), workflowId: 'wf-uuid-123');

        $transport->assertLastRequestBodyContains('workflow_id', 'wf-uuid-123');
    }

    public function test_report_includes_previous_exception_in_description(): void
    {
        [$client, $transport] = $this->makeClient([
            [201, Fixtures::ticketCreatedResponse()],
        ]);

        $previous = new \InvalidArgumentException('Root cause');
        $exception = new \RuntimeException('Wrapper', previous: $previous);

        $client->report($exception);

        $body = json_decode($transport->lastRequest()['body'], true);
        $this->assertStringContainsString('InvalidArgumentException', $body['description']);
        $this->assertStringContainsString('Root cause', $body['description']);
    }

    public function test_report_returns_null_when_api_fails(): void
    {
        [$client] = $this->makeClient([
            [401, Fixtures::authErrorResponse()],
        ]);

        $this->assertNull($client->report(new \RuntimeException('Test')));
    }
}
