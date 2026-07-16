<?php
/**
 * @author Martín Isaí Zapata Ramos
 * @email  martin.isai@zapataramos.com
 */

namespace Workflow\SDK\Exceptions;

/**
 * Thrown when the X-API-KEY is missing, invalid, or the project is inactive (HTTP 401).
 */
class WorkflowAuthException extends WorkflowException {}
