<?php
/**
 * @author Martín Isaí Zapata Ramos
 * @email  martin.isai@zapataramos.com
 */

namespace Workflow\SDK\Exceptions;

/**
 * Thrown for unexpected API errors (5xx or unrecognised 4xx).
 */
class WorkflowApiException extends WorkflowException
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 0,
    ) {
        parent::__construct($message, $statusCode);
    }
}
