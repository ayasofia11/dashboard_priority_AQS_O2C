<?php

namespace App\Services;

use App\Enums\PriorityLevel;
use App\Models\{SalesOrder, PriorityModel, OrderPriorityEvaluation, Customer, Product, StockSnapshot, CustomerSoldeSnapshot};
use Illuminate\Support\Collection;

class PriorityService
{
    // Les 3 populations calculées une seule fois, réutilisées pour toutes les commandes.
    private ?Collection $stockPop = null;
    private ?Collection $soldePop = null;
    private ?Collection $distancePop = null;

    // Cache mémoire : évite de refaire une requête latestStock()/latestSolde()
    // pour le même produit/client déjà consulté dans cet import.
    private array $stockCache = [];
    private array $soldeCache = [];

    private function stockFor(Product $product): ?StockSnapshot
    {
        return $this->stockCache[$product->id] ??= $product->latestStock();
    }

    private function soldeFor(Customer $customer): ?CustomerSoldeSnapshot
    {
        return $this->soldeCache[$customer->id] ??= $customer->latestSolde();
    }

    // Appelée UNE FOIS avant la boucle d'import, pas à chaque commande.
    public function preparePopulations(): void
    {
        $this->stockPop = SalesOrder::where('status', '!=', 'DELIVERED')->with('items.product')->get()
            ->map(fn ($o) => $o->items->sum(fn ($i) => $this->stockFor($i->product)?->available_qty ?? 0));

        $this->soldePop = Customer::all()->map(fn ($c) => $this->soldeFor($c)?->outstanding_solde ?? 0);

        $this->distancePop = Customer::whereHas('customerType', fn ($q) => $q->where('code', '!=', 'IMPORT_EXPORT'))
            ->get()->pluck('distance_km')->filter(fn ($d) => ! is_null($d));
    }

    public function evaluate(SalesOrder $order): ?OrderPriorityEvaluation
    {
        if ($order->status === 'DELIVERED') { return null; }

        if (is_null($this->stockPop)) $this->preparePopulations();

        $order->loadMissing('items.product.productType', 'customer.customerType');

        $model = PriorityModel::where('is_active', true)->firstOrFail();

        $totalRemainingQty = $order->items->sum(fn ($item) => $item->remaining_quantity);

        // --- Étape 1 : blocage, dans l'ordre de priorité des raisons ---
        if ($this->isDataMissing($order)) {
            return $this->persist($order, $model, $totalRemainingQty, null, PriorityLevel::Bloquee, collect(), 'Donnée manquante : stock ou solde inconnu.');
        }

        if ($this->isStockInsufficient($order)) {
            return $this->persist($order, $model, $totalRemainingQty, null, PriorityLevel::Bloquee, collect(), 'Stock insuffisant pour au moins une ligne.');
        }

        if ($this->isSoldeInsufficient($order)) {
            return $this->persist($order, $model, $totalRemainingQty, null, PriorityLevel::Bloquee, collect(), 'Solde client insuffisant pour couvrir la commande.');
        }

        // --- Étape 2 : les 6 scores ---
        $factors = $model->factors->keyBy('code');

        $scores = collect([
            $this->scoreStockLevel($order, $factors['stock_level']),
            $this->scoreOrderAge($order, $factors['order_age']),
            $this->scoreCustomerSolde($order->customer, $factors['customer_solde']),
            $this->scoreCustomerType($order->customer, $factors['customer_type']),
            $this->scoreDeliveryProgress($order, $factors['delivery_progress']),
            $this->scoreProductType($order, $factors['product_type']),
        ]);

        // --- Étape 3 : score final et niveau ---
        $finalScore = round($scores->sum('weighted_score'), 2);
        $level = $this->levelFromScore($finalScore, $model->thresholds);

        return $this->persist($order, $model, $totalRemainingQty, $finalScore, $level, $scores, null);
    }

    // --- Blocage : donnée manquante ---
    private function isDataMissing(SalesOrder $order): bool
    {
        foreach ($order->items as $item) {
            if ($item->remaining_quantity > 0 && is_null($this->stockFor($item->product))) {
                return true;
            }
        }

        return is_null($this->soldeFor($order->customer));
    }

    // --- Blocage : stock insuffisant sur au moins une ligne ---
    private function isStockInsufficient(SalesOrder $order): bool
    {
        foreach ($order->items as $item) {
            if ($item->remaining_quantity <= 0) continue;

            $available = $this->stockFor($item->product)?->available_qty ?? 0;
            if ($available < $item->remaining_quantity) return true;
        }

        return false;
    }

    // --- Blocage : solde client insuffisant pour le montant TTC restant ---
    private function isSoldeInsufficient(SalesOrder $order): bool
    {
        $montantRestantTTC = $order->items->sum(fn ($item) => $item->total_amount);
        $solde = $this->soldeFor($order->customer)?->outstanding_solde ?? 0;

        return $solde < $montantRestantTTC;
    }

    // --- Facteur 1 : stock disponible, brut, comparé aux autres commandes (30%) ---
    private function scoreStockLevel(SalesOrder $order, $factor): array
    {
        $raw = $order->items->sum(fn ($item) => $this->stockFor($item->product)?->available_qty ?? 0);
        $score = $this->minMaxNormalize($raw, $this->stockPop->min(), $this->stockPop->max());

        return $this->result('stock_level', $raw, $score, $factor->weight,
            sprintf('Stock disponible total pour cette commande : %.1f.', $raw));
    }

    // --- Facteur 2 : ancienneté, plus ancienne = plus prioritaire (20%) ---
    private function scoreOrderAge(SalesOrder $order, $factor): array
    {
        $days = $order->order_date->diffInDays(today());
        $maxDays = $factor->config_json['max_days'] ?? 30;
        $score = min($days / $maxDays, 1) * 100;

        return $this->result('order_age', $days, $score, $factor->weight,
            sprintf('Commande passée il y a %d jours.', $days));
    }

    // --- Facteur 3 : solde client, brut, comparé aux autres clients (20%) ---
    private function scoreCustomerSolde(Customer $customer, $factor): array
    {
        $raw = $this->soldeFor($customer)?->outstanding_solde ?? 0;
        $score = $this->minMaxNormalize($raw, $this->soldePop->min(), $this->soldePop->max());

        return $this->result('customer_solde', $raw, $score, $factor->weight,
            sprintf('Solde client : %.0f DZD (le plus élevé est prioritaire).', $raw));
    }

    // --- Facteur 4 : Export = priorité max, sinon distance (10%) ---
    private function scoreCustomerType(Customer $customer, $factor): array
    {
        if ($customer->customerType?->code === 'IMPORT_EXPORT') {
            return $this->result('customer_type', null, 100, $factor->weight, 'Client export : priorité maximale.');
        }

        $distance = $customer->distance_km;
        $score = is_null($distance) ? 50 : $this->minMaxNormalize($distance, $this->distancePop->min(), $this->distancePop->max());

        return $this->result('customer_type', $distance, $score, $factor->weight,
            is_null($distance) ? 'Distance inconnue, score neutre.' : sprintf('Client local à %.0f km.', $distance));
    }

    // --- Facteur 5 : avancement de la livraison, bien avancée = plus prioritaire (10%) ---
    private function scoreDeliveryProgress(SalesOrder $order, $factor): array
    {
        $ordered = $order->items->sum('ordered_quantity');
        $delivered = $order->items->sum('delivered_quantity');
        $progress = $ordered > 0 ? ($delivered / $ordered) * 100 : 0;

        return $this->result('delivery_progress', $ordered - $delivered, $progress, $factor->weight,
            sprintf('%.0f%% déjà livré.', $progress));
    }

    // --- Facteur 6 : type de produit, semi-fini > fini (10%) ---
    private function scoreProductType(SalesOrder $order, $factor): array
    {
        $avg = $order->items->map(fn ($i) => $i->product->productType->priority_score ?? 50)->avg() ?? 50;

        return $this->result('product_type', $avg, $avg, $factor->weight,
            sprintf('Score moyen des types de produits : %.0f.', $avg));
    }

    private function minMaxNormalize(float $raw, float $min, float $max): float
    {
        if ($max <= $min) return 50;   // pas assez d'écart pour comparer : score neutre
        return (($raw - $min) / ($max - $min)) * 100;
    }

    private function result(string $code, ?float $raw, float $normalized, float $weight, string $explanation): array
    {
        return [
            'factor_code' => $code,
            'raw_value' => $raw,
            'normalized_score' => round($normalized, 2),
            'weighted_score' => round($normalized * ($weight / 100), 2),
            'explanation' => $explanation,
        ];
    }

    private function levelFromScore(float $score, array $t): PriorityLevel
    {
        return match(true) {
            $score >= $t['critique']    => PriorityLevel::Critique,
            $score >= $t['urgente']     => PriorityLevel::Urgente,
            $score >= $t['prioritaire'] => PriorityLevel::Prioritaire,
            default => PriorityLevel::Normale,
        };
    }

    private function persist(
        SalesOrder $order, PriorityModel $model, float $totalRemainingQty,
        ?float $finalScore, PriorityLevel $level, Collection $scores, ?string $reason
    ): OrderPriorityEvaluation {
        $evaluation = OrderPriorityEvaluation::create([
            'sales_order_id' => $order->id,
            'priority_model_id' => $model->id,
            'evaluated_at' => now(),
            'total_remaining_qty' => $totalRemainingQty,
            'final_score' => $finalScore,
            'priority_level' => $level,
            'reason' => $reason,
        ]);

        foreach ($scores as $score) {
            $evaluation->factorScores()->create($score);
        }

        return $evaluation;
    }
}
