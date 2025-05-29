<?php

namespace Database\Seeders;

use App\Models\Sale;
use App\Models\Pos;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleToPosMigrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks to avoid issues with constraints
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            // Get all sales with their items
            $sales = Sale::with('items.stockItem')->get();

            $this->command->info("Found {$sales->count()} sales to migrate.");

            // Start a transaction
            DB::beginTransaction();

            $migratedCount = 0;
            $errorCount = 0;

            foreach ($sales as $sale) {
                try {
                    // Skip sales with no items
                    if ($sale->items->isEmpty()) {
                        $this->command->warn("Skipping sale #{$sale->id} - No items found.");
                        continue;
                    }

                    // Convert sale items to the format expected by Pos.sale_items
                    $saleItems = [];
                    foreach ($sale->items as $item) {
                        $saleItems[] = [
                            'stock_item_id' => $item->stock_item_id,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'total_price' => $item->total_price,
                        ];
                    }

                    // Create a new Pos record with data from Sale
                    $pos = new Pos();
                    $pos->customer_id = $sale->customer_id;
                    $pos->vehicle_id = $sale->vehicle_id;
                    $pos->date = $sale->date;
                    $pos->subtotal_amount = $sale->subtotal_amount;
                    $pos->discount_percentage = $sale->discount_percentage;
                    $pos->discount_amount = $sale->discount_amount;
                    $pos->total_amount = $sale->total_amount;
                    $pos->transaction_type = $sale->transaction_type;
                    $pos->quotation_id = $sale->quotation_id;
                    $pos->remarks = $sale->remarks;

                    // Set the sale_items JSON field
                    $pos->sale_items = $saleItems;

                    // Temporarily disable the boot method to avoid stock updates
                    // since the stock was already updated when the original sale was created
                    Pos::unsetEventDispatcher();

                    // Save the Pos record
                    $pos->save();

                    // Reset the event dispatcher
                    Pos::setEventDispatcher(app('events'));

                    $migratedCount++;
                    $this->command->info("Migrated sale #{$sale->id} to pos #{$pos->id}");
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->command->error("Error migrating sale #{$sale->id}: {$e->getMessage()}");
                    Log::error("Error migrating sale #{$sale->id}: {$e->getMessage()}");
                    Log::error($e->getTraceAsString());
                }
            }

            // Commit the transaction
            DB::commit();

            $this->command->info("Migration completed. Migrated: {$migratedCount}, Errors: {$errorCount}");

        } catch (\Exception $e) {
            // Rollback the transaction if an error occurs
            DB::rollBack();

            $this->command->error("Migration failed: {$e->getMessage()}");
            Log::error("Migration failed: {$e->getMessage()}");
            Log::error($e->getTraceAsString());
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
