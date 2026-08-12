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

    /**
     * Absolute path of the host application's root, used to make error
     * fingerprints stable across deploys (see fingerprint()). The Laravel
     * provider passes base_path(); plain-PHP callers may pass their project
     * root, or leave it null to rely on the release-path heuristic.
     */
    private readonly ?string $projectRoot;

    public function __construct(
        string     $apiKey,
        string     $baseUrl,
        string     $helpdeskUrl = '',
        int        $retries     = 2,
        ?Transport $transport   = null,
        int        $timeout     = 10,
        ?string    $projectRoot = null,
    ) {
        $this->projectRoot = $projectRoot;
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
            // Stable hash so the platform can fold repeated occurrences of the
            // same error into a single ticket instead of creating duplicates.
            'fingerprint' => $this->fingerprint($exception, $context),
        ];

        // Which end-client was being served when the error happened — lets the
        // platform count distinct clients impacted. Resolved by the host app
        // (see workflow.client_resolver) and passed through the context.
        if (isset($context['client_external_id'])
            && $context['client_external_id'] !== null
            && $context['client_external_id'] !== ''
        ) {
            $data['client_external_id'] = (string) $context['client_external_id'];
        }

        if ($workflowId !== null) {
            $data['workflow_id'] = $workflowId;
        }

        return $this->tickets->create($data);
    }

    // ── Private ───────────────────────────────────────────────

    /**
     * Computes a stable fingerprint that groups "the same" error.
     *
     * Uses the exception class plus the file and line where it was thrown —
     * NOT the message, which often carries variable data (ids, values) that
     * would defeat grouping. Two occurrences with the same origin collapse
     * into one ticket on the platform.
     *
     * The host app may override grouping entirely by passing an explicit
     * `fingerprint` in the context.
     *
     * @param array<string, mixed> $context
     */
    private function fingerprint(\Throwable $e, array $context = []): string
    {
        // Explicit override — let the host control how errors are grouped.
        if (isset($context['fingerprint'])
            && is_string($context['fingerprint'])
            && $context['fingerprint'] !== ''
        ) {
            return sha1($context['fingerprint']);
        }

        return sha1(get_class($e) . '|' . $this->normalizePath($e->getFile()) . '|' . $e->getLine());
    }

    /**
     * Makes a file path stable across deploys and servers so the fingerprint
     * doesn't change every release.
     *
     * Atomic-deploy tools (Forge, Envoyer, Deployer, Capistrano) and container
     * rebuilds place the app under a release directory whose name changes on
     * every deploy (e.g. `…/releases/20260810123456/app/Foo.php`). Using the
     * raw absolute path there yields a different sha1 after each deploy, so the
     * same bug never folds and duplicate tickets pile up. We strip the known
     * project root and collapse any release-id segment to a relative path.
     */
    private function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        // 1) Strip the configured project root (Laravel passes base_path()).
        if ($this->projectRoot !== null && $this->projectRoot !== '') {
            $root = rtrim(str_replace('\\', '/', $this->projectRoot), '/') . '/';
            if (str_starts_with($path, $root)) {
                return substr($path, strlen($root));
            }
        }

        // 2) Fallback: collapse a timestamped/hashed release segment so
        //    atomic-deploy paths stop fragmenting the fingerprint.
        return preg_replace('#/releases/[^/]+/#', '/releases/', $path) ?? $path;
    }

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
