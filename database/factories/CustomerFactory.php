<?php
// database/factories/CustomerFactory.php
namespace Database\Factories;

use App\Models\CustomerType;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_type_id' => CustomerType::factory(),
            'customer_code' => 'CLI-' . $this->faker->unique()->numerify('#####'),
            'name' => $this->faker->company(),
            'distance_km' => $this->faker->numberBetween(5, 500),
            'is_active' => true,
        ];
    }
}
