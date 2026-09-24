<?php
// database/factories/CustomerSoldeSnapshotFactory.php
namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerSoldeSnapshotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'outstanding_solde' => $this->faker->numberBetween(100000, 5000000),
            'currency' => 'DZD',
            'snapshot_at' => now(),
        ];
    }
}
