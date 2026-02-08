<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('price_monthly', 8, 2)->default(0);
            $table->decimal('price_yearly', 8, 2)->default(0);
            $table->integer('max_invoices_per_month')->default(3);
            $table->integer('max_clients')->default(5);
            $table->integer('max_products')->default(10);
            $table->boolean('custom_logo')->default(false);
            $table->boolean('custom_templates')->default(false);
            $table->boolean('recurring_invoices')->default(false);
            $table->boolean('remove_watermark')->default(false);
            $table->boolean('export_csv')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
