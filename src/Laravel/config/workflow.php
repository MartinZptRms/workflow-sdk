<?php
/**
 * @author Martín Isaí Zapata Ramos
 * @email  martin.isai@zapataramos.com
 */

return [

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    | Your project's X-API-KEY generated from the Workflow platform.
    | Settings → Project Info → Integración API → Generar clave
    */
    'api_key' => env('WORKFLOW_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    | The base URL of your Workflow instance.
    | Do NOT include a trailing slash or the /v1 prefix.
    */
    'base_url' => env('WORKFLOW_BASE_URL', 'https://your-domain.com'),

    /*
    |--------------------------------------------------------------------------
    | Helpdesk URL
    |--------------------------------------------------------------------------
    | The URL of your Workflow helpdesk interface — where your clients land
    | after login. The SDK appends ?token= to this URL.
    |
    | Defaults to base_url + '/helpdesk' if not set.
    | Example: https://your-domain.com/helpdesk
    */
    'helpdesk_url' => env('WORKFLOW_HELPDESK_URL'),

    /*
    |--------------------------------------------------------------------------
    | Support Workflow ID
    |--------------------------------------------------------------------------
    | Optional. When your project has multiple support workflows, set this to
    | target a specific one on every request. Can be overridden per-call.
    | Leave null to let the platform auto-select (only valid if there is exactly
    | one support workflow assigned to the project).
    | Find this ID in Project Info → Integración API → Workflows de soporte.
    */
    'support_id' => env('WORKFLOW_SUPPORT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Automatic 500 Reporting
    |--------------------------------------------------------------------------
    | When enabled, any unhandled exception that would result in a 500 response
    | is automatically reported as a critical ticket — no extra code required.
    |
    | Set WORKFLOW_AUTO_REPORT=false to disable and handle reporting manually.
    */
    'auto_report' => env('WORKFLOW_AUTO_REPORT', true),

    /*
    |--------------------------------------------------------------------------
    | HTTP Options
    |--------------------------------------------------------------------------
    */
    'timeout' => env('WORKFLOW_TIMEOUT', 30),
    'retries' => env('WORKFLOW_RETRIES', 2),

];
