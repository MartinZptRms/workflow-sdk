<?php
/**
 * @author Martín Isaí Zapata Ramos
 * @email  martin.isai@zapataramos.com
 */

namespace Workflow\SDK\Http;

/**
 * Pluggable HTTP transport layer.
 *
 * The default implementation uses cURL. Tests inject a MockTransport
 * to avoid real HTTP calls. Custom implementations can wrap Guzzle,
 * Symfony HttpClient, etc.
 *
 * Returns [$httpStatusCode, $rawResponseBody].
 */
interface Transport
{
    /**
     * @param  array<string, string> $headers
     * @return array{0: int, 1: string}   [status, body]
     */
    public function send(
        string  $method,
        string  $url,
        array   $headers,
        ?string $body = null,
    ): array;
}
