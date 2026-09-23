<?php
// database/seeders/CustomerTypeSeeder.php
namespace Database\Seeders;

use App\Models\CustomerType;
use Illuminate\Database\Seeder;

class CustomerTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['IMPORT_EXPORT', 'Import-Export simulé'],
            ['DISTRIBUTEUR', 'Distributeur simulé'],
            ['TRANSFORMATEUR', 'Transformateur simulé'],
            ['UTILISATEUR', 'Utilisateur simulé'],
        ] as [$code, $name]) {
            CustomerType::updateOrCreate(['code' => $code], ['name' => $name]);
        }
    }
}
