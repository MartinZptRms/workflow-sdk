<?php
/**
 * @author Martín Isaí Zapata Ramos
 * @email  martin.isai@zapataramos.com
 */

namespace Workflow\SDK\Tests\Unit\Exceptions;

use PHPUnit\Framework\TestCase;
use Workflow\SDK\Exceptions\WorkflowValidationException;

class WorkflowValidationExceptionTest extends TestCase
{
    public function test_has_error_returns_true_for_existing_field(): void
    {
        $e = new WorkflowValidationException('Validation failed.', [
            'title' => ['The title field is required.'],
        ]);

        $this->assertTrue($e->hasError('title'));
        $this->assertFalse($e->hasError('description'));
    }

    public function test_get_error_returns_first_message(): void
    {
        $e = new WorkflowValidationException('Validation failed.', [
            'workflow_id' => ['The selected workflow is not a support workflow.'],
        ]);

        $this->assertSame(
            'The selected workflow is not a support workflow.',
            $e->getError('workflow_id')
        );
    }

    public function test_get_error_returns_null_for_missing_field(): void
    {
        $e = new WorkflowValidationException('Validation failed.', []);

        $this->assertNull($e->getError('nonexistent'));
    }
}
