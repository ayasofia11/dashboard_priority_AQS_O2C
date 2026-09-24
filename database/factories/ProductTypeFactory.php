<?php
// database/factories/ProductTypeFactory.php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->word()),
            'name' => $this->faker->word(),
            'priority_score' => 50,
        ];
    }
}
