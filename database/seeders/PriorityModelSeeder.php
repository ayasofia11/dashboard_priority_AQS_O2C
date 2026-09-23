<?php
// database/seeders/PriorityModelSeeder.php
namespace Database\Seeders;

use App\Models\{PriorityModel, PriorityFactor};
use Illuminate\Database\Seeder;

class PriorityModelSeeder extends Seeder
{
    public function run(): void
    {
        $model = PriorityModel::updateOrCreate(
            ['name' => 'Modèle standard', 'version' => 'v1'],
            ['is_active' => true, 'thresholds' => ['critique' => 80, 'urgente' => 65, 'prioritaire' => 50]]
        );

        foreach ([
            ['code' => 'stock_level',       'weight' => 30, 'config_json' => null],
            ['code' => 'order_age',         'weight' => 20, 'config_json' => ['max_days' => 30]],
            ['code' => 'customer_solde',    'weight' => 20, 'config_json' => null],
            ['code' => 'customer_type',     'weight' => 10, 'config_json' => null],
            ['code' => 'delivery_progress', 'weight' => 10, 'config_json' => null],
            ['code' => 'product_type',      'weight' => 10, 'config_json' => null],
        ] as $f) {
            PriorityFactor::updateOrCreate(
                ['priority_model_id' => $model->id, 'code' => $f['code']],
                ['weight' => $f['weight'], 'config_json' => $f['config_json']]
            );
        }
    }
}
