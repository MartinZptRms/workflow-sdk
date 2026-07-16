<?php

namespace Workflow\SDK\Resources;

use Workflow\SDK\Data\HelpdeskToken;
use Workflow\SDK\Http\HttpClient;

/**
 * Provides helpdesk self-service authentication for tenant clients.
 *
 * ── Usage (Laravel) ──────────────────────────────────────────────────────────
 *
 *   // In any controller method where the user is logged in:
 *   public function openHelpdesk(Request $request)
 *   {
 *       $result = Workflow::helpdesk()->login(
 *           externalId: (string) auth()->id(),
 *           email:      auth()->user()->email,
 *           name:       auth()->user()->name,
 *       );
 *
 *       return redirect($result->url);
 *       // or: return response()->json(['url' => $result->url]);
 *   }
 *
 * ── Usage (plain PHP) ────────────────────────────────────────────────────────
 *
 *   $result = $client->helpdesk->login('user-123', 'user@example.com', 'John Doe');
 *   header('Location: ' . $result->url);
 */
class HelpdeskResource
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string     $helpdeskUrl,
    ) {}

    /**
     * Authenticates the tenant's end user and returns a token + redirect URL.
     *
     * Call this from your backend when the user clicks "View my tickets".
     * Redirect the user's browser to $result->url — no further action needed.
     *
     * @param string $externalId  The user's ID in your system (any stable unique string).
     * @param string $email       The user's email address.
     * @param string $name        The user's display name.
     *
     * @throws \Workflow\SDK\Exceptions\WorkflowAuthException       on invalid API key (401)
     * @throws \Workflow\SDK\Exceptions\WorkflowValidationException on bad input (422)
     * @throws \Workflow\SDK\Exceptions\WorkflowApiException        on other errors
     */
    public function login(string $externalId, string $email, string $name): HelpdeskToken
    {
        $response = $this->http->post('/helpdesk/login', [
            'external_id' => $externalId,
            'email'       => $email,
            'name'        => $name,
        ]);

        return HelpdeskToken::fromArray($response['data'], $this->helpdeskUrl);
    }
}
