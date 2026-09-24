<?php
// database/factories/SalesOrderFactory.php
namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'order_number' => 'CMD-' . $this->faker->unique()->numerify('#####'),
            'order_date' => now()->subDays($this->faker->numberBetween(1, 20)),
            'status' => 'OPEN',
        ];
    }
}
