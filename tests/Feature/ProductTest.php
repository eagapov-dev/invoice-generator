<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_list_products(): void
    {
        Product::factory()->count(3)->create(['user_id' => $this->user->id]);
        Product::factory()->count(2)->create(); // Other user's products

        $response = $this->actingAs($this->user)
            ->getJson('/api/products');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_product(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/products', [
                'name' => 'Web Development',
                'description' => 'Custom web development service',
                'price' => 150.00,
                'unit' => 'hour',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Web Development')
            ->assertJsonPath('data.unit', 'hour');

        $this->assertEquals(150.00, $response->json('data.price'));

        $this->assertDatabaseHas('products', [
            'user_id' => $this->user->id,
            'name' => 'Web Development',
        ]);
    }

    public function test_user_can_view_own_product(): void
    {
        $product = Product::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/products/{$product->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $product->id);
    }

    public function test_user_cannot_view_other_users_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/products/{$product->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_update_own_product(): void
    {
        $product = Product::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/products/{$product->id}", [
                'name' => 'Updated Product',
                'price' => 200.00,
                'unit' => 'service',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Product');

        $this->assertEquals(200.00, $response->json('data.price'));
    }

    public function test_user_can_delete_own_product(): void
    {
        $product = Product::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_product_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/products', [
                'description' => 'No name or price',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price', 'unit']);
    }

    public function test_product_unit_must_be_valid(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/products', [
                'name' => 'Test Product',
                'price' => 100,
                'unit' => 'invalid_unit',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['unit']);
    }
}
