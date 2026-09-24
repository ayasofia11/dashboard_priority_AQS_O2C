<?php
// database/factories/ProductFactory.php
namespace Database\Factories;

use App\Models\ProductType;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_type_id' => ProductType::factory(),
            'reference' => 'PROD-' . $this->faker->unique()->numerify('#####'),
            'name' => $this->faker->word(),
            'unit' => 'Kg',
            'is_active' => true,
        ];
    }
}
