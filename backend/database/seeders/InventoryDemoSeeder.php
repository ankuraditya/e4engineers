<?php

namespace Database\Seeders;

use App\Enums\InventoryMovementType;
use App\Models\Book;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;

class InventoryDemoSeeder extends Seeder
{
    public function run(): void
    {
        $quantities = [25, 10, 4, 0];
        $service = app(InventoryService::class);
        Book::orderBy('id')->limit(4)->get()->each(function ($book, $index) use ($quantities, $service) {
            $inventory = $service->initialize($book);
            if ($inventory->stock_quantity === 0 && ($quantities[$index] ?? 0) > 0) {
                $service->increase($book, $quantities[$index], InventoryMovementType::InitialStock, 'Demo inventory setup');
            }
        });
    }
}
