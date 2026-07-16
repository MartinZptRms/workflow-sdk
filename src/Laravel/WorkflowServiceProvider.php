<?php
/**
 * @author Martín Isaí Zapata Ramos
 * @email  martin.isai@zapataramos.com
 */

namespace Workflow\SDK\Laravel;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\ServiceProvider;
use Workflow\SDK\WorkflowClient;

class WorkflowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/workflow.php',
            'workflow'
        );

        $this->app->singleton(WorkflowClient::class, function ($app) {
            $config = $app['config']['workflow'];

            return new WorkflowClient(
                apiKey:      $config['api_key']      ?? '',
                baseUrl:     $config['base_url']     ?? '',
                helpdeskUrl: $config['helpdesk_url'] ?? '',
                retries:     (int) ($config['retries'] ?? 2),
                timeout:     (int) ($config['timeout'] ?? 10),
            );
        });

        $this->app->alias(WorkflowClient::class, 'workflow');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/workflow.php' => config_path('workflow.php'),
            ], 'workflow-config');
        }

        $this->registerAutoReport();
    }

    // ── Auto-reporting ────────────────────────────────────────

    private function registerAutoReport(): void
    {
        if (! $this->shouldAutoReport()) {
            return;
        }

        // callAfterResolving ensures we hook into the Handler after the app
        // has fully booted — safe even if the Handler is bound late.
        $this->callAfterResolving(ExceptionHandler::class, function (ExceptionHandler $handler) {
            if (! method_exists($handler, 'reportable')) {
                return;
            }

            $handler->reportable(function (\Throwable $e) {
                // Defer the HTTP call until AFTER the response is sent to the client.
                // This way a slow or unreachable server never blocks the error page.
                $context = $this->buildContext();

                $this->app->terminating(function () use ($e, $context) {
                    rescue(function () use ($e, $context) {
                        /** @var WorkflowClient $client */
                        $client = $this->app->make(WorkflowClient::class);
                        $client->report($e, $context, config('workflow.support_id'));
                    }, report: false);
                });
            });
        });
    }

    private function shouldAutoReport(): bool
    {
        return (bool) config('workflow.auto_report', true)
            && ! empty(config('workflow.api_key'));
    }

    private function buildContext(): array
    {
        $context = [];

        if ($this->app->bound('request')) {
            $request = $this->app->make('request');

            $context['url']    = $request->fullUrl();
            $context['method'] = $request->method();
            $context['ip']     = $request->ip();

            // Never log sensitive fields.
            $context['input'] = $request->except([
                'password', 'password_confirmation',
                'token', 'secret', 'api_key', 'credit_card',
            ]);
        }

        if ($this->app->bound('auth')) {
            try {
                $context['user_id'] = $this->app->make('auth')->id();
            } catch (\Throwable) {
                // Auth guard may not be available in all contexts.
            }
        }

        return array_filter($context, fn($v) => $v !== null && $v !== []);
    }
}
