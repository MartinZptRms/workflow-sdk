<?php
/**
 * @author Martín Isaí Zapata Ramos
 * @email  martin.isai@zapataramos.com
 */

namespace Workflow\SDK\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Workflow\SDK\Data\HelpdeskToken;
use Workflow\SDK\Data\Ticket;
use Workflow\SDK\Data\TicketList;
use Workflow\SDK\Resources\HelpdeskResource;
use Workflow\SDK\Resources\TicketResource;

/**
 * Laravel Facade for the WorkflowClient.
 *
 * @method static TicketResource    tickets()
 * @method static HelpdeskResource  helpdesk()
 * @method static Ticket            ticket(array $data)
 * @method static Ticket            report(\Throwable $exception, array $context = [], ?string $workflowId = null)
 *
 * @see \Workflow\SDK\WorkflowClient
 */
class Workflow extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'workflow';
    }
}
