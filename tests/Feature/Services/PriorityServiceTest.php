<?php

namespace Tests\Feature\Services;

use App\Models\{Customer, CustomerType, Product, ProductType, SalesOrder, SalesOrderItem, StockSnapshot, CustomerSoldeSnapshot};
use App\Services\PriorityService;
use Database\Seeders\{CustomerTypeSeeder, ProductTypeSeeder, PriorityModelSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriorityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CustomerTypeSeeder::class);
        $this->seed(ProductTypeSeeder::class);
        $this->seed(PriorityModelSeeder::class);
    }

    private function makeOrder(array $opts = []): SalesOrder
    {
        $type = CustomerType::where('code', $opts['type_code'] ?? 'DISTRIBUTEUR')->first();
        $customer = Customer::factory()->create([
            'customer_type_id' => $type->id,
            'distance_km' => $opts['distance'] ?? 50,
        ]);

        if (array_key_exists('solde', $opts)) {
            if (! is_null($opts['solde'])) {
                CustomerSoldeSnapshot::factory()->create(['customer_id' => $customer->id, 'outstanding_solde' => $opts['solde']]);
            }
        } else {
            CustomerSoldeSnapshot::factory()->create(['customer_id' => $customer->id, 'outstanding_solde' => 1000000]);
        }

        $ptype = ProductType::where('code', $opts['product_type_code'] ?? 'FINI')->first();
        $product = Product::factory()->create(['product_type_id' => $ptype->id]);

        if (array_key_exists('stock', $opts)) {
            if (! is_null($opts['stock'])) {
                StockSnapshot::factory()->create(['product_id' => $product->id, 'available_qty' => $opts['stock']]);
            }
        } else {
            StockSnapshot::factory()->create(['product_id' => $product->id, 'available_qty' => 1000]);
        }

        $order = SalesOrder::factory()->create([
            'customer_id' => $customer->id,
            'order_date' => now()->subDays($opts['age_days'] ?? 10),
            'status' => $opts['status'] ?? 'OPEN',
        ]);

        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $product->id,
            'ordered_quantity' => $opts['ordered'] ?? 100,
            'delivered_quantity' => $opts['delivered'] ?? 0,
            'unit_price' => 100,
            'tva_rate' => 0.19,
        ]);

        return $order->fresh(['items']);
    }

    // --- Cas d'arrêt précoce ---

    public function test_delivered_order_returns_null(): void
    {
        $order = $this->makeOrder(['status' => 'DELIVERED']);
        $this->assertNull(app(PriorityService::class)->evaluate($order));
    }

    // --- Les 3 règles de blocage ---

    public function test_missing_stock_snapshot_blocks_order(): void
    {
        $order = $this->makeOrder(['stock' => null]);
        $eval = app(PriorityService::class)->evaluate($order);
        $this->assertEquals('bloquee', $eval->priority_level->value);
        $this->assertStringContainsString('manquante', $eval->reason);
    }

    public function test_missing_solde_snapshot_blocks_order(): void
    {
        $order = $this->makeOrder(['solde' => null]);
        $eval = app(PriorityService::class)->evaluate($order);
        $this->assertEquals('bloquee', $eval->priority_level->value);
    }

    public function test_insufficient_stock_blocks_order(): void
    {
        $order = $this->makeOrder(['stock' => 5, 'ordered' => 100, 'delivered' => 0]);
        $eval = app(PriorityService::class)->evaluate($order);
        $this->assertEquals('bloquee', $eval->priority_level->value);
        $this->assertStringContainsString('Stock', $eval->reason);
    }

    public function test_insufficient_solde_blocks_order(): void
    {
        $order = $this->makeOrder(['solde' => 100, 'ordered' => 100, 'delivered' => 0]);
        $eval = app(PriorityService::class)->evaluate($order);
        $this->assertEquals('bloquee', $eval->priority_level->value);
        $this->assertStringContainsString('Solde', $eval->reason);
    }

    public function test_sufficient_stock_and_solde_does_not_block(): void
    {
        $order = $this->makeOrder(['stock' => 1000, 'solde' => 5000000]);
        $eval = app(PriorityService::class)->evaluate($order);
        $this->assertNotEquals('bloquee', $eval->priority_level->value);
        $this->assertNotNull($eval->final_score);
    }

    // --- Facteur : type client ---

    public function test_export_customer_scores_100_on_customer_type(): void
    {
        $order = $this->makeOrder(['type_code' => 'IMPORT_EXPORT']);
        $eval = app(PriorityService::class)->evaluate($order);
        $score = $eval->factorScores()->where('factor_code', 'customer_type')->first();
        $this->assertEquals(100, $score->normalized_score);
    }

    public function test_local_customer_farther_scores_higher_than_closer(): void
    {
        $near = $this->makeOrder(['type_code' => 'DISTRIBUTEUR', 'distance' => 10]);
        $far  = $this->makeOrder(['type_code' => 'DISTRIBUTEUR', 'distance' => 900]);

        $service = app(PriorityService::class);
        $evalNear = $service->evaluate($near);
        $evalFar = $service->evaluate($far);

        $scoreNear = $evalNear->factorScores()->where('factor_code', 'customer_type')->first()->normalized_score;
        $scoreFar = $evalFar->factorScores()->where('factor_code', 'customer_type')->first()->normalized_score;

        $this->assertGreaterThan($scoreNear, $scoreFar);
    }

    // --- Facteur : ancienneté ---

    public function test_older_order_scores_higher_on_age(): void
    {
        $young = $this->makeOrder(['age_days' => 2]);
        $old = $this->makeOrder(['age_days' => 30]);

        $service = app(PriorityService::class);
        $scoreYoung = $service->evaluate($young)->factorScores()->where('factor_code', 'order_age')->first()->normalized_score;
        $scoreOld = $service->evaluate($old)->factorScores()->where('factor_code', 'order_age')->first()->normalized_score;

        $this->assertGreaterThan($scoreYoung, $scoreOld);
        $this->assertEquals(100, $scoreOld);   // 30 jours = le max configuré
    }

    // --- Facteur : solde client ---

    public function test_higher_solde_scores_higher(): void
    {
        $low = $this->makeOrder(['solde' => 200000]);
        $high = $this->makeOrder(['solde' => 4000000]);

        $service = app(PriorityService::class);
        $scoreLow = $service->evaluate($low)->factorScores()->where('factor_code', 'customer_solde')->first()->normalized_score;
        $scoreHigh = $service->evaluate($high)->factorScores()->where('factor_code', 'customer_solde')->first()->normalized_score;

        $this->assertGreaterThan($scoreLow, $scoreHigh);
    }

    // --- Facteur : stock disponible ---

    public function test_higher_stock_scores_higher(): void
    {
        $low = $this->makeOrder(['stock' => 50, 'ordered' => 10]);
        $high = $this->makeOrder(['stock' => 2000, 'ordered' => 10]);

        $service = app(PriorityService::class);
        $scoreLow = $service->evaluate($low)->factorScores()->where('factor_code', 'stock_level')->first()->normalized_score;
        $scoreHigh = $service->evaluate($high)->factorScores()->where('factor_code', 'stock_level')->first()->normalized_score;

        $this->assertGreaterThan($scoreLow, $scoreHigh);
    }

    // --- Facteur : avancement de livraison ---

    public function test_more_delivered_scores_higher_on_progress(): void
    {
        $order = $this->makeOrder(['ordered' => 100, 'delivered' => 60]);
        $eval = app(PriorityService::class)->evaluate($order);
        $score = $eval->factorScores()->where('factor_code', 'delivery_progress')->first();
        $this->assertEquals(60, $score->normalized_score);
    }

    // --- Facteur : type produit ---

    public function test_semi_fini_scores_higher_than_fini(): void
    {
        $fini = $this->makeOrder(['product_type_code' => 'FINI']);
        $semiFini = $this->makeOrder(['product_type_code' => 'SEMI_FINI']);

        $service = app(PriorityService::class);
        $scoreFini = $service->evaluate($fini)->factorScores()->where('factor_code', 'product_type')->first()->normalized_score;
        $scoreSemiFini = $service->evaluate($semiFini)->factorScores()->where('factor_code', 'product_type')->first()->normalized_score;

        $this->assertGreaterThan($scoreFini, $scoreSemiFini);
    }

    // --- Score final et seuils ---

    public function test_final_score_matches_expected_level(): void
    {
        $order = $this->makeOrder([
            'type_code' => 'IMPORT_EXPORT', 'age_days' => 30, 'solde' => 5000000,
            'stock' => 2000, 'ordered' => 100, 'delivered' => 90, 'product_type_code' => 'SEMI_FINI',
        ]);

        $eval = app(PriorityService::class)->evaluate($order);
        $this->assertContains($eval->priority_level->value, ['critique', 'urgente']);
    }
}
