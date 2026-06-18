<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use Tests\TestCase;

class ConnectedApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_connected_applications_page_lists_active_oauth_clients(): void
    {
        $user = User::factory()->create();
        $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
            name: 'Claude Desktop',
            redirectUris: ['http://localhost:6274/oauth/callback'],
            confidential: false,
        );

        $this->createAccessToken($user, $client->id, ['mcp:use']);

        $this->actingAs($user)
            ->get(route('connected-applications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/connected-applications')
                ->has('applications', 1)
                ->where('applications.0.id', $client->id)
                ->where('applications.0.name', 'Claude Desktop')
                ->where('applications.0.redirectUris.0', 'http://localhost:6274/oauth/callback')
                ->where('applications.0.scopes.0', 'mcp:use')
                ->where('applications.0.tokenCount', 1),
            );
    }

    public function test_disconnect_revokes_only_current_users_tokens_for_client(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
            name: 'Claude Desktop',
            redirectUris: ['http://localhost:6274/oauth/callback'],
            confidential: false,
        );

        $token = $this->createAccessToken($user, $client->id, ['mcp:use']);
        $otherToken = $this->createAccessToken($otherUser, $client->id, ['mcp:use']);

        $this->actingAs($user)
            ->delete(route('connected-applications.destroy', $client))
            ->assertRedirect();

        $this->assertTrue($token->fresh()->revoked);
        $this->assertTrue($token->refreshToken->fresh()->revoked);
        $this->assertFalse($otherToken->fresh()->revoked);
        $this->assertFalse($otherToken->refreshToken->fresh()->revoked);
    }

    /**
     * @param  array<int, string>  $scopes
     */
    private function createAccessToken(User $user, string $clientId, array $scopes): Token
    {
        /** @var Token $token */
        $token = Passport::token()->newQuery()->forceCreate([
            'id' => hash('sha256', Str::random(40)),
            'user_id' => $user->id,
            'client_id' => $clientId,
            'name' => null,
            'scopes' => $scopes,
            'revoked' => false,
            'expires_at' => now()->addDay(),
        ]);

        Passport::refreshToken()->newQuery()->forceCreate([
            'id' => hash('sha256', Str::random(40)),
            'access_token_id' => $token->id,
            'revoked' => false,
            'expires_at' => now()->addMonth(),
        ]);

        return $token->load('refreshToken');
    }
}
