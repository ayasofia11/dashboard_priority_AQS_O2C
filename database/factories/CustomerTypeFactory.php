<?php
// database/factories/CustomerTypeFactory.php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->word()),
            'name' => $this->faker->word(),
        ];
    }
}
