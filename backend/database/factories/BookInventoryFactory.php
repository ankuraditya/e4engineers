<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookInventoryFactory extends Factory
{
    public function definition(): array
    {
        return ['book_id' => Book::factory(), 'stock_quantity' => 20, 'reserved_quantity' => 0, 'low_stock_threshold' => 5, 'is_backorder_allowed' => false];
    }

    public function inStock(): static
    {
        return $this->state(['stock_quantity' => 20, 'low_stock_threshold' => 5]);
    }

    public function lowStock(): static
    {
        return $this->state(['stock_quantity' => 3, 'low_stock_threshold' => 5]);
    }

    public function outOfStock(): static
    {
        return $this->state(['stock_quantity' => 0]);
    }
}
