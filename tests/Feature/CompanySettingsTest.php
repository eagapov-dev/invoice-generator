<?php

namespace Tests\Feature;

use App\Models\CompanySettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanySettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_get_settings(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/settings');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'company_name',
                    'address',
                    'phone',
                    'email',
                    'bank_details',
                    'default_currency',
                    'default_tax_percent',
                ],
            ]);
    }

    public function test_settings_are_created_if_not_exists(): void
    {
        $this->assertDatabaseMissing('company_settings', [
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/settings');

        $response->assertOk();
        $this->assertDatabaseHas('company_settings', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_can_update_settings(): void
    {
        CompanySettings::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->putJson('/api/settings', [
                'company_name' => 'My Company',
                'address' => '123 Main St',
                'phone' => '+1234567890',
                'email' => 'company@example.com',
                'bank_details' => 'Bank: Test Bank\nAccount: 1234567890',
                'default_currency' => 'EUR',
                'default_tax_percent' => 20,
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'company_name' => 'My Company',
                    'default_currency' => 'EUR',
                    'default_tax_percent' => 20,
                ],
            ]);

        $this->assertDatabaseHas('company_settings', [
            'user_id' => $this->user->id,
            'company_name' => 'My Company',
        ]);
    }

    public function test_user_can_upload_logo(): void
    {
        Storage::fake('public');
        CompanySettings::factory()->create(['user_id' => $this->user->id]);

        $file = UploadedFile::fake()->image('logo.png', 200, 200);

        $response = $this->actingAs($this->user)
            ->postJson('/api/settings/logo', [
                'logo' => $file,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['logo', 'logo_url'],
                'message',
            ]);

        Storage::disk('public')->assertExists('logos/' . $file->hashName());
    }

    public function test_logo_must_be_an_image(): void
    {
        Storage::fake('public');
        CompanySettings::factory()->create(['user_id' => $this->user->id]);

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->user)
            ->postJson('/api/settings/logo', [
                'logo' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['logo']);
    }

    public function test_logo_max_size_is_2mb(): void
    {
        Storage::fake('public');
        CompanySettings::factory()->create(['user_id' => $this->user->id]);

        $file = UploadedFile::fake()->image('logo.png')->size(3000); // 3MB

        $response = $this->actingAs($this->user)
            ->postJson('/api/settings/logo', [
                'logo' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['logo']);
    }

    public function test_settings_validation(): void
    {
        CompanySettings::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->putJson('/api/settings', [
                'email' => 'not-an-email',
                'default_tax_percent' => 150, // > 100
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'default_tax_percent']);
    }
}
