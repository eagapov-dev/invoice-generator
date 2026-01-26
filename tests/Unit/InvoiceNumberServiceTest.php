<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceNumberServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceNumberService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InvoiceNumberService;
        $this->user = User::factory()->create();
    }

    public function test_generates_first_invoice_number(): void
    {
        $number = $this->service->generate($this->user);

        $this->assertEquals('INV-0001', $number);
    }

    public function test_generates_sequential_invoice_numbers(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'invoice_number' => 'INV-0001',
        ]);

        $number = $this->service->generate($this->user);

        $this->assertEquals('INV-0002', $number);
    }

    public function test_handles_gaps_in_invoice_numbers(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'invoice_number' => 'INV-0005',
        ]);

        $number = $this->service->generate($this->user);

        $this->assertEquals('INV-0006', $number);
    }

    public function test_invoice_numbers_are_user_specific(): void
    {
        $client1 = Client::factory()->create(['user_id' => $this->user->id]);
        $user2 = User::factory()->create();
        $client2 = Client::factory()->create(['user_id' => $user2->id]);

        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client1->id,
            'invoice_number' => 'INV-0010',
        ]);

        Invoice::factory()->create([
            'user_id' => $user2->id,
            'client_id' => $client2->id,
            'invoice_number' => 'INV-0003',
        ]);

        $number1 = $this->service->generate($this->user);
        $number2 = $this->service->generate($user2);

        $this->assertEquals('INV-0011', $number1);
        $this->assertEquals('INV-0004', $number2);
    }
}
