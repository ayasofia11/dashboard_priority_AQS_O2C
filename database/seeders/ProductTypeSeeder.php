<?php
// database/seeders/ProductTypeSeeder.php
namespace Database\Seeders;

use App\Models\ProductType;
use Illuminate\Database\Seeder;

class ProductTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['SEMI_FINI', 'Semi fini', 100],
            ['FINI', 'Fini', 50],
        ] as [$code, $name, $score]) {
            ProductType::updateOrCreate(['code' => $code], ['name' => $name, 'priority_score' => $score]);
        }
    }
}
