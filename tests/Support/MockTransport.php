<?php
/**
 * @author Martín Isaí Zapata Ramos
 * @email  martin.isai@zapataramos.com
 */

namespace Workflow\SDK\Tests\Support;

use Workflow\SDK\Http\Transport;

/**
 * In-memory transport for unit tests. No real HTTP calls.
 *
 * Usage:
 *   $transport = new MockTransport([
 *       [201, ['data' => $ticketArray, 'code' => 'created']],
 *       [200, ['data' => [$ticketArray], 'meta' => $metaArray, 'code' => 'ok']],
 *   ]);
 *
 *   $client = new WorkflowClient('key', 'http://localhost', transport: $transport);
 */
class MockTransport implements Transport
{
    /** @var list<array{0: int, 1: array<string, mixed>}> */
    private array $queue;

    /** @var list<array{method: string, url: string, headers: array, body: string|null}> */
    private array $requests = [];

    /**
     * @param list<array{0: int, 1: array<string, mixed>}> $responses
     *   Each entry: [httpStatus, responseBodyArray]
     */
    public function __construct(array $responses = [])
    {
        $this->queue = $responses;
    }

    public function send(string $method, string $url, array $headers, ?string $body = null): array
    {
        $this->requests[] = compact('method', 'url', 'headers', 'body');

        if (empty($this->queue)) {
            return [200, json_encode(['data' => [], 'meta' => [], 'code' => 'ok'])];
        }

        [$status, $responseBody] = array_shift($this->queue);

        return [$status, json_encode($responseBody)];
    }

    public function lastRequest(): array
    {
        return end($this->requests) ?: [];
    }

    public function requestCount(): int
    {
        return count($this->requests);
    }

    public function assertRequestCount(int $expected): void
    {
        \PHPUnit\Framework\Assert::assertCount(
            $expected,
            $this->requests,
            "Expected {$expected} request(s), got " . count($this->requests) . '.'
        );
    }

    public function assertLastRequestBodyContains(string $key, mixed $value): void
    {
        $body = json_decode($this->lastRequest()['body'] ?? '{}', true);
        \PHPUnit\Framework\Assert::assertSame($value, $body[$key] ?? null);
    }
}
