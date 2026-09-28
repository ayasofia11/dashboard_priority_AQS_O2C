<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use Illuminate\Http\Request;

class SalesOrderController extends Controller
{
    /**
     * Liste des commandes évaluées, avec recherche et filtres combinables.
     * Les commandes DELIVERED n'apparaissent jamais ici : elles n'ont aucune
     * évaluation (PriorityService::evaluate() renvoie null pour elles), et le
     * JOIN ci-dessous exige qu'une évaluation existe.
     */
    public function index(Request $request)
    {
        $orders = SalesOrder::query()
            ->join('order_priority_evaluations as latest_eval', function ($join) {
                $join->on('latest_eval.sales_order_id', '=', 'sales_orders.id')
                     ->whereRaw('latest_eval.id = (SELECT MAX(id) FROM order_priority_evaluations WHERE sales_order_id = sales_orders.id)');
            })
            ->select('sales_orders.*', 'latest_eval.priority_level', 'latest_eval.final_score', 'latest_eval.reason', 'latest_eval.id as evaluation_id')
            ->where('sales_orders.status', '!=', 'DELIVERED') 
            ->with('customer', 'lastImportBatch', 'items.product')

            // --- Recherche et filtres, appliqués seulement si présents dans l'URL ---

            ->when($request->filled('order_number'), fn ($q) =>
                $q->where('sales_orders.order_number', 'like', '%' . $request->order_number . '%'))

            ->when($request->filled('client'), fn ($q) =>
                $q->whereHas('customer', fn ($q2) =>
                    $q2->where('name', 'like', '%' . $request->client . '%')))

            ->when($request->filled('product_ref'), fn ($q) =>
                $q->whereHas('items.product', fn ($q2) =>
                    $q2->where('reference', 'like', '%' . $request->product_ref . '%')))

            ->when($request->filled('priority'), fn ($q) =>
                $q->where('latest_eval.priority_level', $request->priority))

            ->when($request->filled('status'), fn ($q) =>
                $q->where('sales_orders.status', $request->status))

            ->when($request->filled('import_batch_id'), fn ($q) =>
                $q->where('sales_orders.last_import_batch_id', $request->import_batch_id))

            ->when($request->boolean('stock_alert'), fn ($q) =>
                $q->where('latest_eval.priority_level', 'bloquee')
                    ->where('latest_eval.reason', 'like', '%Stock%'))

            ->when($request->filled('date_from'), fn ($q) =>
                $q->whereDate('sales_orders.order_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) =>
                $q->whereDate('sales_orders.order_date', '<=', $request->date_to))

            // --- Tri : ordre métier de priorité, pas alphabétique ---
            ->orderByRaw("FIELD(latest_eval.priority_level, 'bloquee', 'critique', 'urgente', 'prioritaire', 'normale')")
            ->orderByDesc('sales_orders.order_date')

            ->paginate(20)
            ->withQueryString();

        // Totaux par commande, calculés en mémoire (pas stockés), ajoutés à chaque ligne du résultat.
        $orders->getCollection()->transform(function ($order) {
            $order->total_ordered_qty = $order->items->sum('ordered_quantity');
            $order->total_delivered_qty = $order->items->sum('delivered_quantity');
            $order->total_remaining_qty = $order->items->sum(fn ($i) => $i->remaining_quantity);
            $order->total_amount_ttc = $order->items->sum(fn ($i) => $i->total_amount);
            return $order;
        });

        return response()->json($orders);
    }

    /**
     * Détail d'une commande : ses lignes, le résultat de la dernière évaluation,
     * le détail des 6 facteurs de score (ou la raison si bloquée), et la décision
     * du planificateur si elle existe.
     */
    public function show(SalesOrder $order)
    {
        $order->load([
            'customer.customerType',
            'items.product.productType',
            'lastImportBatch',
            'latestPriorityEvaluation.factorScores',
            'latestPriorityEvaluation.decision.decidedBy',
        ]);

        return response()->json([
            'order' => [
                'order_number' => $order->order_number,
                'order_date' => $order->order_date,
                'requested_delivery_date' => $order->requested_delivery_date,
                'confirmed_delivery_date' => $order->confirmed_delivery_date,
                'status' => $order->status,
                'customer' => [
                    'name' => $order->customer->name,
                    'type' => $order->customer->customerType?->name,
                    'distance_km' => $order->customer->distance_km,
                ],
                'import_origin' => $order->lastImportBatch ? [
                    'id' => $order->lastImportBatch->id,
                    'file_name' => $order->lastImportBatch->file_name,
                    'imported_at' => $order->lastImportBatch->imported_at,
                ] : null,
                'observation' => $order->observation,
            ],
            'lines' => $order->items->map(fn ($item) => [
                'line_number' => $item->line_number,
                'product_reference' => $item->product->reference,
                'product_name' => $item->product->name,
                'product_type' => $item->product->productType?->name,
                'unit_price' => $item->unit_price,
                'tva_rate' => $item->tva_rate,
                'ordered_quantity' => $item->ordered_quantity,
                'delivered_quantity' => $item->delivered_quantity,
                'remaining_quantity' => $item->remaining_quantity,
                'total_amount' => $item->total_amount,
                'availability' => is_null($item->product->latestStock())
                    ? 'Stock non communiqué'
                    : ($item->product->latestStock()->available_qty < $item->remaining_quantity ? 'Insuffisant' : 'Suffisant'),
                'status' => $item->status,
            ]),
            'evaluation' => $order->latestPriorityEvaluation ? [
                'id' => $order->latestPriorityEvaluation->id,
                'evaluated_at' => $order->latestPriorityEvaluation->evaluated_at,
                'priority_level' => $order->latestPriorityEvaluation->priority_level->value,
                'color' => $order->latestPriorityEvaluation->priority_level->color(),
                'final_score' => $order->latestPriorityEvaluation->final_score,
                'reason' => $order->latestPriorityEvaluation->reason,
                'factors' => $order->latestPriorityEvaluation->factorScores->map(fn ($f) => [
                    'code' => $f->factor_code,
                    'raw_value' => $f->raw_value,
                    'normalized_score' => $f->normalized_score,
                    'weighted_score' => $f->weighted_score,
                    'explanation' => $f->explanation,
                ]),
                'decision' => $order->latestPriorityEvaluation->decision ? [
                    'decision' => $order->latestPriorityEvaluation->decision->decision,
                    'final_priority_level' => $order->latestPriorityEvaluation->decision->final_priority_level,
                    'comment' => $order->latestPriorityEvaluation->decision->comment,
                    'decided_by' => $order->latestPriorityEvaluation->decision->decidedBy->name,
                    'decided_at' => $order->latestPriorityEvaluation->decision->decided_at,
                ] : null,
            ] : null,
        ]);
    }
}
