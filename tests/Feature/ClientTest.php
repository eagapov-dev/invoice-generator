<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_list_clients(): void
    {
        Client::factory()->count(3)->create(['user_id' => $this->user->id]);
        Client::factory()->count(2)->create(); // Other user's clients

        $response = $this->actingAs($this->user)
            ->getJson('/api/clients');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_client(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/clients', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+1234567890',
                'company' => 'Acme Inc',
                'address' => '123 Main St',
                'notes' => 'Important client',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'John Doe')
            ->assertJsonPath('data.email', 'john@example.com');

        $this->assertDatabaseHas('clients', [
            'user_id' => $this->user->id,
            'name' => 'John Doe',
        ]);
    }

    public function test_user_can_view_own_client(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/clients/{$client->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $client->id);
    }

    public function test_user_cannot_view_other_users_client(): void
    {
        $client = Client::factory()->create(); // Another user's client

        $response = $this->actingAs($this->user)
            ->getJson("/api/clients/{$client->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_update_own_client(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/clients/{$client->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_user_can_delete_own_client(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/clients/{$client->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }

    public function test_user_can_search_clients(): void
    {
        Client::factory()->create(['user_id' => $this->user->id, 'name' => 'John Doe']);
        Client::factory()->create(['user_id' => $this->user->id, 'name' => 'Jane Smith']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/clients?search=John');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_client_name_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/clients', [
                'email' => 'test@example.com',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
