<?php
/**
 * @author Martín Isaí Zapata Ramos
 * @email  martin.isai@zapataramos.com
 */

namespace Workflow\SDK\Resources;

use Workflow\SDK\Data\Ticket;
use Workflow\SDK\Data\TicketList;
use Workflow\SDK\Http\HttpClient;

/**
 * Provides all ticket-related API operations.
 *
 * Accessed via $client->tickets.
 */
class TicketResource
{
    public function __construct(
        private readonly HttpClient $http,
    ) {}

    /**
     * Create a new ticket. Returns null when the API returns an error.
     *
     * @param array{
     *   title:               string,
     *   description?:        string,
     *   priority?:           'low'|'medium'|'high'|'critical',
     *   workflow_id?:        string,
     *   item_type_id?:       string,
     *   client_external_id?: string,
     *   external_id?:        string,
     *   metadata?:           array<string, mixed>,
     * } $data
     */
    public function create(array $data): ?Ticket
    {
        $response = $this->http->post('/v1/tickets', $data);

        if (empty($response['data'])) {
            return null;
        }

        return Ticket::fromArray($response['data']);
    }

    /**
     * List tickets for the authenticated project.
     *
     * @param array{
     *   client_external_id?: string,
     *   status_key?:         string,
     *   only_open?:          bool,
     *   per_page?:           int,
     *   sort_by?:            'created_at'|'updated_at'|'priority',
     *   sort_dir?:           'asc'|'desc',
     * } $params
     */
    public function list(array $params = []): TicketList
    {
        $response = $this->http->get('/v1/tickets', $params);

        return TicketList::fromArray($response);
    }

    /**
     * Retrieve a single ticket by its UUID. Returns null when not found or on error.
     */
    public function find(string $id): ?Ticket
    {
        $response = $this->http->get("/v1/tickets/{$id}");

        if (empty($response['data'])) {
            return null;
        }

        return Ticket::fromArray($response['data']);
    }
}
