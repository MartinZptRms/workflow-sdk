<?php

/**
 * Workflow SDK — Plain PHP usage
 *
 * No framework required. Works with any PHP 8.2+ project.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Workflow\SDK\Exceptions\WorkflowApiException;
use Workflow\SDK\Exceptions\WorkflowAuthException;
use Workflow\SDK\Exceptions\WorkflowValidationException;
use Workflow\SDK\WorkflowClient;

$client = new WorkflowClient(
    apiKey:  $_ENV['WORKFLOW_API_KEY'] ?? 'your-api-key',
    baseUrl: $_ENV['WORKFLOW_BASE_URL'] ?? 'https://your-domain.com',
);

// ── 1. Create a ticket (user-submitted) ──────────────────────────────────────

$ticket = $client->tickets->create([
    'title'              => 'My order hasn\'t arrived',
    'description'        => 'Order #12345 was placed 7 days ago and has not been delivered.',
    'priority'           => 'high',
    'client_external_id' => 'usr_789',  // links to clients.external_id in Workflow
]);

echo "Ticket created: {$ticket->id} — status: {$ticket->status->name}\n";

// ── 2. Create a ticket on a specific support workflow ────────────────────────

$ticket = $client->tickets->create([
    'title'       => 'Payment gateway timeout',
    'priority'    => 'critical',
    'workflow_id' => 'your-support-workflow-uuid', // from project-info → Integración API
]);

// ── 3. Auto-report a PHP exception ───────────────────────────────────────────

try {
    // some risky operation...
    throw new \RuntimeException('Database connection refused');
} catch (\Throwable $e) {
    $ticket = $client->report($e, [
        'server'     => gethostname(),
        'php_version'=> PHP_VERSION,
    ]);

    echo "Incident reported: {$ticket->id}\n";
}

// ── 4. List tickets for a client ─────────────────────────────────────────────

$list = $client->tickets->list([
    'client_external_id' => 'usr_789',
    'only_open'          => true,
    'per_page'           => 10,
]);

echo "Open tickets for usr_789: {$list->count()} (total: {$list->meta->total})\n";

foreach ($list as $t) {
    echo "  [{$t->priority}] {$t->title} — {$t->status->name}\n";
}

if ($list->hasMore()) {
    echo "  … and {$list->meta->total} more on subsequent pages\n";
}

// ── 5. Get a single ticket ────────────────────────────────────────────────────

$ticket = $client->tickets->find('ticket-uuid');
echo "Ticket: {$ticket->title} | Closed: " . ($ticket->isClosed() ? 'yes' : 'no') . "\n";

// ── 6. Error handling ─────────────────────────────────────────────────────────

try {
    $client->tickets->create(['title' => 'test']);
} catch (WorkflowAuthException $e) {
    echo "Invalid API key: {$e->getMessage()}\n";
} catch (WorkflowValidationException $e) {
    echo "Validation error: {$e->getMessage()}\n";
    if ($e->hasError('workflow_id')) {
        echo "  workflow_id: {$e->getError('workflow_id')}\n";
    }
} catch (WorkflowApiException $e) {
    echo "API error {$e->statusCode}: {$e->getMessage()}\n";
}
