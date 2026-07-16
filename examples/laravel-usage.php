<?php

/**
 * Workflow SDK — Laravel usage
 *
 * After installing:
 *   composer require workflow/sdk
 *
 * Publish config (optional):
 *   php artisan vendor:publish --tag=workflow-config
 *
 * .env:
 *   WORKFLOW_API_KEY=your-api-key
 *   WORKFLOW_BASE_URL=https://your-domain.com
 *   WORKFLOW_SUPPORT_ID=optional-workflow-uuid
 */

// ── Via Facade ────────────────────────────────────────────────────────────────

use Workflow\SDK\Exceptions\WorkflowApiException;
use Workflow\SDK\Laravel\Facades\Workflow;

// Create a ticket
$ticket = Workflow::tickets()->create([
    'title'              => 'Payment failed',
    'priority'           => 'high',
    'client_external_id' => $user->external_id,
    'workflow_id'        => config('workflow.support_id'),
]);

// Shortcut
$ticket = Workflow::ticket([
    'title'    => 'Something broke',
    'priority' => 'critical',
]);

// List tickets
$list = Workflow::tickets()->list([
    'client_external_id' => $user->external_id,
    'only_open'          => true,
]);

// Auto-report an exception
Workflow::report($exception, ['user_id' => $user->id, 'url' => $request->url()]);

// ── Via dependency injection ───────────────────────────────────────────────────

use Workflow\SDK\WorkflowClient;

class SupportController extends Controller
{
    public function __construct(
        private readonly WorkflowClient $workflow,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority'    => ['sometimes', 'in:low,medium,high,critical'],
        ]);

        try {
            $ticket = $this->workflow->ticket([
                ...$validated,
                'client_external_id' => $request->user()->customer_id,
            ]);

            return response()->json([
                'ticket_id' => $ticket->id,
                'status'    => $ticket->status->name,
            ], 201);

        } catch (WorkflowApiException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

// ── Auto-reporting in the exception handler (App\Exceptions\Handler) ─────────

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    public function register(): void
    {
        // Only report production errors — avoids noise in dev
        $this->reportable(function (Throwable $e) {
            if (app()->isProduction()) {
                rescue(fn () => Workflow::report($e, [
                    'user_id' => auth()->id(),
                    'url'     => request()->fullUrl(),
                    'method'  => request()->method(),
                ]));
            }
        });
    }
}
