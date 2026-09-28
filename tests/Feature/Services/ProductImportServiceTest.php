<?php

namespace Tests\Feature\Services;

use App\Models\{Product, ProductType, StockSnapshot};
use App\Services\ProductImportService;
use Database\Seeders\ProductTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\User;
use Tests\TestCase;

class ProductImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ProductTypeSeeder::class);
        $this->userId = User::factory()->create()->id;
    }

    // Construit un petit fichier .xlsx en mémoire avec les en-têtes attendues par
    // ProductImportService::COLUMN_MAP, pour ne pas dépendre d'un fichier sur disque.
    private function makeProductFile(float $availableQty): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray([
            ['Réf. produit', 'Produit', 'Unité', 'Type produit', 'Qte disponible'],
            ['TEST-P001', 'Produit Test', 'Tonne', 'Semi fini', $availableQty],
        ]);

        $path = tempnam(sys_get_temp_dir(), 'products_test_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        // 'test' => permet à Laravel de traiter le fichier comme un vrai upload en test.
        return new UploadedFile($path, 'products_test.xlsx', null, null, true);
    }

    public function test_first_import_creates_one_stock_snapshot(): void
    {
        app(ProductImportService::class)->import($this->makeProductFile(500), $this->userId);

        $product = Product::where('reference', 'TEST-P001')->first();

        $this->assertNotNull($product);
        $this->assertEquals(1, $product->stockSnapshots()->count());
        $this->assertEquals(500, $product->latestStock()->available_qty);
    }

    // Le vrai test du correctif : réimporter avec la MÊME quantité ne doit rien ajouter.
    public function test_reimport_with_same_quantity_does_not_duplicate_snapshot(): void
    {
        app(ProductImportService::class)->import($this->makeProductFile(500), $this->userId);
        app(ProductImportService::class)->import($this->makeProductFile(500), $this->userId);

        $product = Product::where('reference', 'TEST-P001')->first();

        $this->assertEquals(1, $product->stockSnapshots()->count());
    }

    // Réimporter avec une quantité DIFFÉRENTE doit créer un nouveau snapshot (historique préservé).
    public function test_reimport_with_different_quantity_creates_new_snapshot(): void
    {
        app(ProductImportService::class)->import($this->makeProductFile(500), $this->userId);
        app(ProductImportService::class)->import($this->makeProductFile(320), $this->userId);

        $product = Product::where('reference', 'TEST-P001')->first();

        $this->assertEquals(2, $product->stockSnapshots()->count());
        $this->assertEquals(320, $product->latestStock()->available_qty);
    }

    // Trois imports identiques d'affilée : toujours 1 seul snapshot, pas 3.
    public function test_three_identical_imports_still_produce_one_snapshot(): void
    {
        app(ProductImportService::class)->import($this->makeProductFile(500), $this->userId);
        app(ProductImportService::class)->import($this->makeProductFile(500), $this->userId);
        app(ProductImportService::class)->import($this->makeProductFile(500), $this->userId);

        $product = Product::where('reference', 'TEST-P001')->first();

        $this->assertEquals(1, $product->stockSnapshots()->count());
    }
}
