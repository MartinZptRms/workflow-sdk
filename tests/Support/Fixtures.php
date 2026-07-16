<?php
/**
 * @author Martín Isaí Zapata Ramos
 * @email  martin.isai@zapataramos.com
 */

namespace Workflow\SDK\Tests\Support;

/**
 * Shared test fixtures — API response shapes matching the Workflow API contract.
 */
class Fixtures
{
    public static function ticket(array $overrides = []): array
    {
        return array_merge([
            'id'          => 'ticket-uuid-001',
            'title'       => 'Test ticket',
            'description' => 'Test description',
            'priority'    => 'medium',
            'source'      => 'api',
            'workflow_id' => 'workflow-uuid-001',
            'client_id'   => null,
            'created_by'  => null,
            'closed_at'   => null,
            'created_at'  => '2026-05-15T10:00:00Z',
            'updated_at'  => '2026-05-15T10:00:00Z',
            'status'      => [
                'id'         => 'state-uuid-001',
                'name'       => 'To Do',
                'key'        => 'todo',
                'color'      => '#6B7280',
                'is_initial' => true,
                'is_closed'  => false,
            ],
            'type' => [
                'id'    => 'type-uuid-001',
                'name'  => 'Support',
                'key'   => 'support',
                'color' => '#3B82F6',
            ],
        ], $overrides);
    }

    public static function paginationMeta(array $overrides = []): array
    {
        return array_merge([
            'current_page' => 1,
            'last_page'    => 1,
            'per_page'     => 15,
            'total'        => 1,
            'from'         => 1,
            'to'           => 1,
        ], $overrides);
    }

    public static function ticketListResponse(array $tickets = [], array $metaOverrides = []): array
    {
        return [
            'data' => empty($tickets) ? [self::ticket()] : $tickets,
            'meta' => self::paginationMeta($metaOverrides),
            'code' => 'ok',
        ];
    }

    public static function ticketCreatedResponse(array $ticketOverrides = []): array
    {
        return [
            'data'    => self::ticket($ticketOverrides),
            'message' => 'Ticket created successfully.',
            'code'    => 'created',
        ];
    }

    public static function validationErrorResponse(array $errors = []): array
    {
        return [
            'message' => 'Validation failed.',
            'code'    => 'unprocessable',
            'errors'  => empty($errors) ? ['title' => ['The title field is required.']] : $errors,
        ];
    }

    public static function authErrorResponse(): array
    {
        return [
            'error'   => 'Unauthorized',
            'code'    => 'invalid_api_key',
            'message' => 'Invalid or inactive API key.',
        ];
    }
}
