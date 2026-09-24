<?php
// database/factories/SalesOrderItemFactory.php
namespace Database\Factories;

use App\Models\{SalesOrder, Product};
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesOrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sales_order_id' => SalesOrder::factory(),
            'product_id' => Product::factory(),
            'line_number' => 10,
            'unit_price' => $this->faker->numberBetween(50, 500),
            'tva_rate' => 0.19,
            'ordered_quantity' => 100,
            'delivered_quantity' => 0,
            'initial_delivered_quantity' => 0,
            'status' => 'OPEN',
        ];
    }
}
