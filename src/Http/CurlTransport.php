<?php
/**
 * @author Martín Isaí Zapata Ramos
 * @email  martin.isai@zapataramos.com
 */

namespace Workflow\SDK\Http;

use Workflow\SDK\Exceptions\WorkflowException;

/**
 * Default cURL-based transport. Zero external dependencies.
 */
class CurlTransport implements Transport
{
    public function __construct(
        private readonly int $timeout = 10,
    ) {}

    public function send(string $method, string $url, array $headers, ?string $body = null): array
    {
        $ch = curl_init();

        $curlHeaders = array_map(
            fn(string $name, string $value) => "{$name}: {$value}",
            array_keys($headers),
            array_values($headers),
        );

        curl_setopt_array($ch, [
            CURLOPT_URL                => $url,
            CURLOPT_RETURNTRANSFER     => true,
            CURLOPT_NOSIGNAL           => 1,
            CURLOPT_TIMEOUT_MS         => $this->timeout * 1000,
            CURLOPT_CONNECTTIMEOUT_MS  => min(5, $this->timeout) * 1000,
            CURLOPT_HTTPHEADER         => $curlHeaders,
            CURLOPT_CUSTOMREQUEST      => $method,
            CURLOPT_FOLLOWLOCATION     => false,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $status   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);

        curl_close($ch);

        if ($response === false) {
            throw new WorkflowException("HTTP request failed: {$error}");
        }

        return [$status, (string) $response];
    }
}
