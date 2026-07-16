<?php
/**
 * @author Martín Isaí Zapata Ramos
 * @email  martin.isai@zapataramos.com
 */

namespace Workflow\SDK\Http;

/**
 * Internal HTTP client.
 *
 * Handles auth headers, JSON encoding/decoding, and transparent retry
 * with exponential backoff for 5xx errors. Never throws — callers check
 * the returned array for an 'error' key when needed.
 */
class HttpClient
{
    private readonly Transport $transport;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly int    $retries   = 2,
        ?Transport               $transport = null,
        int                      $timeout   = 10,
    ) {
        $this->transport = $transport ?? new CurlTransport($timeout);
    }

    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, query: $query);
    }

    public function post(string $path, array $body = []): array
    {
        return $this->request('POST', $path, body: $body);
    }

    // ── Internal ──────────────────────────────────────────────

    private function request(
        string $method,
        string $path,
        array  $query = [],
        array  $body  = [],
    ): array {
        $url     = $this->buildUrl($path, $query);
        $headers = $this->buildHeaders();
        $payload = empty($body) ? null : json_encode($body, JSON_THROW_ON_ERROR);

        $attempt = 0;

        do {
            $attempt++;

            [$status, $raw] = $this->transport->send($method, $url, $headers, $payload);

            // Retry only on 5xx with backoff.
            if ($status < 500 || $attempt > $this->retries) {
                break;
            }

            usleep($this->backoff($attempt));

        } while ($attempt <= $this->retries);

        return $this->decode($status, $raw);
    }

    private function decode(int $status, string $raw): array
    {
        return json_decode($raw, true) ?? [];
    }

    private function buildUrl(string $path, array $query = []): string
    {
        $url = rtrim($this->baseUrl, '/') . $path;

        $filtered = array_filter($query, fn($v) => $v !== null && $v !== '');

        if (! empty($filtered)) {
            $url .= '?' . http_build_query($filtered);
        }

        return $url;
    }

    private function buildHeaders(): array
    {
        return [
            'X-API-KEY'    => $this->apiKey,
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    private function backoff(int $attempt): int
    {
        // 200ms, 400ms, 800ms… capped at 5s
        return min(200_000 * (2 ** ($attempt - 1)), 5_000_000);
    }
}
