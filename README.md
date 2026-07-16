# Workflow SDK for PHP

Official PHP SDK for the [Workflow](https://your-domain.com) project management platform.
Create and manage support tickets from any external PHP system.

## Requirements

- PHP 8.2+
- `ext-curl`
- `ext-json`

## Installation

```bash
composer require workflow/sdk
```

## Automatic 500 Reporting (Laravel)

Install, add two env vars — done. Every unhandled exception that would produce a 500 response is automatically reported as a `critical` ticket. No extra code required.

```env
WORKFLOW_API_KEY=your-api-key
WORKFLOW_BASE_URL=https://your-domain.com
```

Each ticket includes: exception class + message, stack trace, request URL, HTTP method, IP, user ID, and input (passwords and tokens are automatically stripped).

To opt out:
```env
WORKFLOW_AUTO_REPORT=false
```

---

## Quick Start

### Plain PHP

```php
use Workflow\SDK\WorkflowClient;

$client = new WorkflowClient(
    apiKey:  'your-api-key',   // from Workflow → Project Info → Integración API
    baseUrl: 'https://your-domain.com',
);

// Create a ticket
$ticket = $client->tickets->create([
    'title'    => 'Error 500 on /checkout',
    'priority' => 'critical',
]);

echo $ticket->id;           // uuid
echo $ticket->status->name; // "To Do"
```

### Laravel

The service provider registers automatically via [Package Discovery](https://laravel.com/docs/packages#package-discovery).

**Publish config (optional):**
```bash
php artisan vendor:publish --tag=workflow-config
```

**`.env`:**
```env
WORKFLOW_API_KEY=your-api-key
WORKFLOW_BASE_URL=https://your-domain.com
WORKFLOW_SUPPORT_ID=  # optional — target a specific support workflow
```

**Usage via Facade:**
```php
use Workflow\SDK\Laravel\Facades\Workflow;

$ticket = Workflow::ticket(['title' => 'Something broke', 'priority' => 'high']);
```

**Usage via dependency injection:**
```php
use Workflow\SDK\WorkflowClient;

class SupportController extends Controller
{
    public function __construct(
        private readonly WorkflowClient $workflow,
    ) {}
}
```

---

## API Reference

### `tickets->create(array $data): Ticket`

Create a new support ticket.

| Field | Type | Required | Description |
|---|---|---|---|
| `title` | `string` | ✅ | Short summary of the issue |
| `description` | `string` | — | Full details. Markdown supported |
| `priority` | `low\|medium\|high\|critical` | — | Default: `medium` |
| `workflow_id` | `string` (UUID) | — | Required if the project has multiple support workflows |
| `item_type_id` | `string` (UUID) | — | Override the item type. Auto-detected if omitted |
| `client_external_id` | `string` | — | Your system's customer ID. Links to the client record |
| `metadata` | `array` | — | Arbitrary structured data attached to the ticket |

```php
$ticket = $client->tickets->create([
    'title'              => 'Order not delivered',
    'description'        => 'Order #12345 placed 7 days ago.',
    'priority'           => 'high',
    'client_external_id' => 'usr_789',
    'workflow_id'        => 'uuid-of-support-workflow',
]);
```

### `tickets->list(array $params = []): TicketList`

List tickets for the authenticated project.

| Param | Type | Description |
|---|---|---|
| `client_external_id` | `string` | Filter by your customer's ID |
| `status_key` | `string` | Filter by workflow state key (e.g. `todo`, `in_progress`) |
| `only_open` | `bool` | `true` = exclude closed tickets |
| `per_page` | `int` | Results per page (1–100, default 15) |
| `sort_by` | `string` | `created_at` \| `updated_at` \| `priority` |
| `sort_dir` | `string` | `asc` \| `desc` |

```php
$list = $client->tickets->list([
    'client_external_id' => 'usr_789',
    'only_open'          => true,
]);

echo $list->count();          // items on this page
echo $list->meta->total;      // all matching tickets
echo $list->hasMore() ? 'more pages' : 'last page';

foreach ($list as $ticket) {
    echo "[{$ticket->priority}] {$ticket->title} — {$ticket->status->name}\n";
}

// Convenience filters
$open   = $list->open();    // Ticket[]
$closed = $list->closed();  // Ticket[]
```

### `tickets->find(string $id): Ticket`

Retrieve a single ticket by UUID.

```php
$ticket = $client->tickets->find('ticket-uuid');

echo $ticket->isOpen()          ? 'open'   : 'closed';
echo $ticket->isHighPriority()  ? 'urgent' : 'normal';
```

### `report(\Throwable $exception, array $context = [], ?string $workflowId = null): Ticket`

Auto-report a PHP exception as a `critical` ticket. The description includes
the exception class, message, stack trace, and any extra context — formatted as Markdown.

```php
// In a catch block
try {
    $response = $httpClient->post('/payment', $data);
} catch (\Throwable $e) {
    $client->report($e, [
        'user_id'  => $userId,
        'endpoint' => '/payment',
        'payload'  => $data,
    ]);
}
```

**In a Laravel exception handler:**
```php
// app/Exceptions/Handler.php
$this->reportable(function (\Throwable $e) {
    if (app()->isProduction()) {
        rescue(fn () => Workflow::report($e, [
            'user_id' => auth()->id(),
            'url'     => request()->fullUrl(),
        ]));
    }
});
```

---

## The `Ticket` object

```php
$ticket->id            // string — UUID
$ticket->title         // string
$ticket->description   // ?string
$ticket->priority      // 'low'|'medium'|'high'|'critical'
$ticket->source        // 'api'
$ticket->workflowId    // string — UUID
$ticket->clientId      // ?string — UUID of the linked client
$ticket->closedAt      // ?string — ISO 8601
$ticket->createdAt     // string  — ISO 8601
$ticket->updatedAt     // string  — ISO 8601

// Status (workflow state)
$ticket->status->id        // string
$ticket->status->name      // string  e.g. "In Progress"
$ticket->status->key       // string  e.g. "in_progress"
$ticket->status->color     // string  e.g. "#3B82F6"
$ticket->status->isInitial // bool
$ticket->status->isClosed  // bool

// Type (item type)
$ticket->type->id    // string
$ticket->type->name  // string  e.g. "Support"
$ticket->type->key   // string  e.g. "support"
$ticket->type->color // string

// Helpers
$ticket->isOpen()          // bool
$ticket->isClosed()        // bool
$ticket->isHighPriority()  // bool — true for 'high' and 'critical'
```

---

## Error Handling

All exceptions extend `Workflow\SDK\Exceptions\WorkflowException`.

```php
use Workflow\SDK\Exceptions\WorkflowAuthException;
use Workflow\SDK\Exceptions\WorkflowNotFoundException;
use Workflow\SDK\Exceptions\WorkflowValidationException;
use Workflow\SDK\Exceptions\WorkflowApiException;

try {
    $ticket = $client->tickets->create($data);

} catch (WorkflowAuthException $e) {
    // HTTP 401 — invalid or missing X-API-KEY
    log_error('Workflow auth failed: ' . $e->getMessage());

} catch (WorkflowValidationException $e) {
    // HTTP 422 — field-level validation errors
    if ($e->hasError('workflow_id')) {
        echo $e->getError('workflow_id'); // "The selected workflow is not a support workflow."
    }
    print_r($e->errors); // all field errors

} catch (WorkflowNotFoundException $e) {
    // HTTP 404 — ticket or resource not found

} catch (WorkflowApiException $e) {
    // Other HTTP errors
    echo "HTTP {$e->statusCode}: {$e->getMessage()}";
}
```

---

## Configuration (Laravel)

`config/workflow.php` after publishing:

```php
return [
    'api_key'             => env('WORKFLOW_API_KEY'),
    'base_url'            => env('WORKFLOW_BASE_URL', 'https://your-domain.com'),
    'support_id' => env('WORKFLOW_SUPPORT_ID'),  // optional
    'timeout'             => env('WORKFLOW_TIMEOUT', 30),
    'retries'             => env('WORKFLOW_RETRIES', 2),
];
```

### Finding your API Key and Workflow IDs

1. Open your Workflow instance
2. Navigate to the project → **Project Info**
3. Scroll to **Integración API**
4. Copy the **X-API-KEY** and the **Workflow ID** of the support workflow you want to target

---

## Retry Behaviour

Network errors and `5xx` responses are automatically retried with exponential backoff:

| Attempt | Wait |
|---|---|
| 1 (first) | — |
| 2 | 200ms |
| 3 | 400ms |

Maximum retries defaults to `2` (3 total attempts). Configure via `retries` param / env.

---

## Testing

```bash
composer install
./vendor/bin/phpunit
```

To mock HTTP in your own tests, inject a `MockTransport`:

```php
use Workflow\SDK\Tests\Support\MockTransport;
use Workflow\SDK\WorkflowClient;

$transport = new MockTransport([
    [201, ['data' => $ticketArray, 'code' => 'created', 'message' => 'Ticket created successfully.']],
]);

$client = new WorkflowClient('any-key', 'https://example.com', transport: $transport);
$ticket = $client->tickets->create(['title' => 'Test']);

$transport->assertRequestCount(1);
$transport->assertLastRequestBodyContains('title', 'Test');
```

---

## Changelog

### 0.1.0
- Initial release
- `tickets->create()`, `tickets->list()`, `tickets->find()`
- `report()` for automatic exception reporting
- Laravel Service Provider, Facade, and publishable config
- Full test suite with `MockTransport`
