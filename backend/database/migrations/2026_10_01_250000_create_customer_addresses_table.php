<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('type', 20)->default('home');
            $t->string('full_name', 120);
            $t->string('mobile', 10);
            $t->string('address_line_1', 255);
            $t->string('address_line_2', 255)->nullable();
            $t->string('landmark', 150)->nullable();
            $t->string('city', 100);
            $t->string('state', 100);
            $t->string('postal_code', 6)->index();
            $t->char('country_code', 2)->default('IN');
            $t->boolean('is_default')->default(false);
            $t->timestamps();
            $t->softDeletes();
            $t->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
