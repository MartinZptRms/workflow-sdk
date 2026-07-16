<?php
/**
 * @author Martín Isaí Zapata Ramos
 * @email  martin.isai@zapataramos.com
 */

namespace Workflow\SDK;

use Workflow\SDK\Data\Ticket;
use Workflow\SDK\Http\HttpClient;
use Workflow\SDK\Http\Transport;
use Workflow\SDK\Resources\HelpdeskResource;
use Workflow\SDK\Resources\TicketResource;

/**
 * Main entry point for the Workflow SDK.
 *
 * ── Plain PHP ────────────────────────────────────────────────────────────────
 *
 *   $client = new WorkflowClient(
 *       apiKey:  'your-api-key',
 *       baseUrl: 'https://your-domain.com',
 *   );
 *
 *   $ticket  = $client->tickets->create(['title' => 'Error 500 on /checkout']);
 *   $list    = $client->tickets->list(['only_open' => true]);
 *   $ticket  = $client->tickets->find('uuid');
 *
 * ── Helpdesk SSO ─────────────────────────────────────────────────────────────
 *
 *   $result = $client->helpdesk->login('user-123', 'user@example.com', 'Jane Doe');
 *   header('Location: ' . $result->url);   // redirect the end user
 *
 * ── Convenience shortcuts ────────────────────────────────────────────────────
 *
 *   // Create a ticket directly
 *   $ticket = $client->ticket(['title' => 'Something broke']);
 *
 *   // Auto-report a PHP exception as a critical ticket
 *   $client->report($exception, ['user_id' => 42, 'url' => '/api/checkout']);
 *
 * ── Laravel — uses Facade instead of newing up the client ───────────────────
 *   See WorkflowServiceProvider and Workflow facade.
 */
class WorkflowClient
{
    public readonly TicketResource   $tickets;
    public readonly HelpdeskResource $helpdesk;

    private readonly HttpClient $http;

    public function __construct(
        string     $apiKey,
        string     $baseUrl,
        string     $helpdeskUrl = '',
        int        $retries     = 2,
        ?Transport $transport   = null,
        int        $timeout     = 10,
    ) {
        $this->http     = new HttpClient($apiKey, $baseUrl, $retries, $transport, $timeout);
        $this->tickets  = new TicketResource($this->http);
        $this->helpdesk = new HelpdeskResource($this->http, $helpdeskUrl ?: $baseUrl . '/helpdesk');
    }

    /**
     * Shortcut for $client->tickets->create($data).
     *
     * @param array<string, mixed> $data
     */
    public function ticket(array $data): ?Ticket
    {
        return $this->tickets->create($data);
    }

    /**
     * Automatically reports a PHP exception as a critical-priority ticket.
     *
     * The description includes the exception class, message, and stack trace
     * formatted as Markdown, plus any extra context you provide.
     * Title and description are truncated to stay within API limits.
     *
     * Returns null silently when the API is unreachable or returns an error.
     *
     * @param array<string, mixed> $context    Extra metadata attached to the description.
     * @param string|null          $workflowId Target a specific support workflow.
     */
    public function report(
        \Throwable $exception,
        array      $context    = [],
        ?string    $workflowId = null,
    ): ?Ticket {
        $data = [
            'title'       => mb_substr(
                get_class($exception) . ': ' . $exception->getMessage(),
                0,
                255,
            ),
            'description' => $this->formatException($exception, $context),
            'priority'    => 'critical',
        ];

        if ($workflowId !== null) {
            $data['workflow_id'] = $workflowId;
        }

        return $this->tickets->create($data);
    }

    // ── Private ───────────────────────────────────────────────

    private const MAX_DESCRIPTION = 9_900;

    private function formatException(\Throwable $e, array $context): string
    {
        // Fixed parts: never truncated.
        $header = implode("\n", [
            '**' . get_class($e) . '**',
            '',
            '> ' . $e->getMessage(),
            '',
            '**File:** `' . $e->getFile() . ':' . $e->getLine() . '`',
            '',
            '**Stack Trace:**',
            '```',
        ]);

        $footer = '';
        if ($e->getPrevious()) {
            $footer .= "\n\n**Caused by:** " . get_class($e->getPrevious()) . ': ' . $e->getPrevious()->getMessage();
        }
        if (! empty($context)) {
            $footer .= "\n\n**Context:**\n```json\n"
                . json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                . "\n```";
        }

        // Assemble with full stack trace.
        $traceLines = explode("\n", $e->getTraceAsString());
        $full = $header . "\n" . implode("\n", $traceLines) . "\n```" . $footer;

        if (mb_strlen($full) <= self::MAX_DESCRIPTION) {
            return $full;
        }

        // Description exceeds limit: remove stack trace frames from the bottom
        // until it fits, preserving the context and "caused by" sections intact.
        $suffix = "\n... (truncated)\n```" . $footer;
        while (! empty($traceLines)) {
            array_pop($traceLines);
            if (mb_strlen($header . "\n" . implode("\n", $traceLines) . $suffix) <= self::MAX_DESCRIPTION) {
                break;
            }
        }

        return $header . "\n" . implode("\n", $traceLines) . $suffix;
    }
}
