<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Services\InventoryService;
use Illuminate\Console\Command;

class InitializeBookInventory extends Command
{
    protected $signature = 'e4engineers:inventory-initialize';

    protected $description = 'Create zero-stock inventory records for books that do not have one';

    public function handle(InventoryService $inventory): int
    {
        $count = 0;
        Book::withTrashed()->doesntHave('inventory')->eachById(function ($book) use ($inventory, &$count) {
            $inventory->initialize($book);
            $count++;
        });
        $this->info("Created {$count} missing inventory record(s). Existing inventory was not changed.");

        return self::SUCCESS;
    }
}
