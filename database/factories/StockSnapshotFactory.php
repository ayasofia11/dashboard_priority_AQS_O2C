<?php
// database/factories/StockSnapshotFactory.php
namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockSnapshotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'available_qty' => $this->faker->numberBetween(0, 2000),
            'snapshot_at' => now(),
        ];
    }
}
