<?php
/**
 * @author Martín Isaí Zapata Ramos
 * @email  martin.isai@zapataramos.com
 */

namespace Workflow\SDK\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Workflow\SDK\Laravel\WorkflowServiceProvider;
use Workflow\SDK\WorkflowClient;

class LaravelServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [WorkflowServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('workflow.api_key', 'test-key-from-config');
        $app['config']->set('workflow.base_url', 'https://test.example.com');
    }

    public function test_client_is_resolved_from_container(): void
    {
        $client = $this->app->make(WorkflowClient::class);

        $this->assertInstanceOf(WorkflowClient::class, $client);
    }

    public function test_client_is_a_singleton(): void
    {
        $a = $this->app->make(WorkflowClient::class);
        $b = $this->app->make(WorkflowClient::class);

        $this->assertSame($a, $b);
    }

    public function test_client_is_accessible_via_alias(): void
    {
        $client = $this->app->make('workflow');

        $this->assertInstanceOf(WorkflowClient::class, $client);
    }

    public function test_config_is_merged_with_defaults(): void
    {
        $this->assertSame('test-key-from-config', config('workflow.api_key'));
        $this->assertSame('https://test.example.com', config('workflow.base_url'));
        $this->assertSame(30, config('workflow.timeout'));
        $this->assertSame(2, config('workflow.retries'));
    }

    public function test_config_file_is_publishable(): void
    {
        $this->artisan('vendor:publish', [
            '--tag'      => 'workflow-config',
            '--provider' => WorkflowServiceProvider::class,
        ])->assertSuccessful();

        $this->assertFileExists(config_path('workflow.php'));

        @unlink(config_path('workflow.php'));
    }
}
