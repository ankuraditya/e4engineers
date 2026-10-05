<?php

namespace Database\Factories;

use App\Enums\InventoryMovementType;
use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryMovementFactory extends Factory
{
    public function definition(): array
    {
        return ['book_id' => Book::factory(), 'type' => InventoryMovementType::ManualIncrease, 'quantity' => 10, 'quantity_before' => 0, 'quantity_after' => 10, 'reason' => 'Factory stock movement', 'created_at' => now()];
    }
}
