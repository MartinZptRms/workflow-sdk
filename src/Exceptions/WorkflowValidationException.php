<?php
/**
 * @author Martín Isaí Zapata Ramos
 * @email  martin.isai@zapataramos.com
 */

namespace Workflow\SDK\Exceptions;

/**
 * Thrown when the API rejects the request due to validation errors (HTTP 422).
 *
 * The $errors array mirrors the API response field-level errors:
 *   ['title' => ['The title field is required.']]
 */
class WorkflowValidationException extends WorkflowException
{
    public function __construct(
        string $message,
        public readonly array $errors = [],
        int $code = 422,
    ) {
        parent::__construct($message, $code);
    }

    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    public function getError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }
}
