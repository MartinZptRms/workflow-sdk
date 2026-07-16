<?php
/**
 * @author Martín Isaí Zapata Ramos
 * @email  martin.isai@zapataramos.com
 */

namespace Workflow\SDK\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Workflow\SDK\Laravel\WorkflowServiceProvider;
use Workflow\SDK\Tests\Support\MockTransport;
use Workflow\SDK\WorkflowClient;

class AutoReportTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [WorkflowServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('workflow.api_key',  'test-key');
        $app['config']->set('workflow.base_url', 'https://test.example.com');
    }

    public function test_auto_report_is_enabled_when_api_key_is_set(): void
    {
        $this->assertTrue((bool) config('workflow.auto_report'));
        $this->assertNotEmpty(config('workflow.api_key'));
    }

    public function test_auto_report_is_skipped_when_api_key_is_empty(): void
    {
        // No exception should be thrown during boot with an empty key.
        $this->app['config']->set('workflow.api_key', '');

        // Re-instantiate the provider to re-run boot with the new config.
        $provider = new WorkflowServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        $this->assertTrue(true); // no exception = pass
    }

    public function test_auto_report_can_be_disabled_via_config(): void
    {
        $this->app['config']->set('workflow.auto_report', false);

        $provider = new WorkflowServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        $this->assertFalse((bool) config('workflow.auto_report'));
    }

    public function test_client_receives_report_on_unhandled_exception(): void
    {
        $transport = new MockTransport([
            [201, [
                'data' => [
                    'id' => 'auto-ticket-uuid', 'title' => 'RuntimeException: Boom',
                    'description' => '', 'priority' => 'critical', 'source' => 'api',
                    'workflow_id' => 'wf-uuid', 'client_id' => null, 'created_by' => null,
                    'closed_at' => null, 'created_at' => '2026-01-01T00:00:00Z',
                    'updated_at' => '2026-01-01T00:00:00Z',
                    'status' => ['id' => 's1', 'name' => 'To Do', 'key' => 'todo', 'color' => '#ccc', 'is_initial' => true, 'is_closed' => false],
                    'type'   => ['id' => 't1', 'name' => 'Support', 'key' => 'support', 'color' => '#ccc'],
                ],
                'message' => 'Ticket created successfully.',
                'code'    => 'created',
            ]],
        ]);

        // Swap the real client for one using our mock transport.
        $this->app->instance(WorkflowClient::class, new WorkflowClient(
            apiKey:    'test-key',
            baseUrl:   'https://test.example.com',
            transport: $transport,
        ));

        // Trigger the reportable callback manually, then fire terminating callbacks.
        $handler = $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $handler->report(new \RuntimeException('Boom'));
        $this->app->terminate();

        // The mock transport should have received exactly one request.
        $transport->assertRequestCount(1);
        $transport->assertLastRequestBodyContains('priority', 'critical');

        $body = json_decode($transport->lastRequest()['body'], true);
        $this->assertStringContainsString('RuntimeException', $body['title']);
        $this->assertStringContainsString('Boom', $body['title']);
    }
}
