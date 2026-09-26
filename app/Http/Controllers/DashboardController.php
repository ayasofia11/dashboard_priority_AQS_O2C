<?php

namespace App\Http\Controllers;

use App\Models\{SalesOrder, ImportBatch};
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private const LEVELS = ['bloquee', 'critique', 'urgente', 'prioritaire', 'normale'];

    public function stats()
    {
        // Compte les commandes par niveau de priorité (dernière évaluation seulement).
        $counts = DB::table('sales_orders')
            ->join('order_priority_evaluations as latest_eval', function ($join) {
                $join->on('latest_eval.sales_order_id', '=', 'sales_orders.id')
                     ->whereRaw('latest_eval.id = (SELECT MAX(id) FROM order_priority_evaluations WHERE sales_order_id = sales_orders.id)');
            })
            ->select('latest_eval.priority_level', DB::raw('count(*) as total'))
            ->groupBy('latest_eval.priority_level')
            ->pluck('total', 'priority_level');

        $byLevel = collect(self::LEVELS)->mapWithKeys(fn ($level) => [$level => (int) ($counts[$level] ?? 0)]);

        // Montant total (TTC) des commandes bloquées, pour donner une idée de l'enjeu financier.
        $blockedAmount = SalesOrder::query()
            ->join('order_priority_evaluations as latest_eval', function ($join) {
                $join->on('latest_eval.sales_order_id', '=', 'sales_orders.id')
                     ->whereRaw('latest_eval.id = (SELECT MAX(id) FROM order_priority_evaluations WHERE sales_order_id = sales_orders.id)');
            })
            ->where('latest_eval.priority_level', 'bloquee')
            ->with('items')
            ->get()
            ->sum(fn ($order) => $order->items->sum(fn ($item) => $item->total_amount));

        // Le dernier import réalisé pour chaque type de fichier (clients, produits, commandes).
        $lastImportIds = ImportBatch::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('source_type')
            ->pluck('id');

        $lastImports = ImportBatch::whereIn('id', $lastImportIds)
            ->get()
            ->keyBy('source_type')
            ->map(fn ($batch) => [
                'file_name' => $batch->file_name,
                'imported_at' => $batch->imported_at,
                'success_rows' => $batch->success_rows,
                'error_rows' => $batch->error_rows,
            ]);

        $stockAlertCount = SalesOrder::query()
             ->join('order_priority_evaluations as latest_eval', function ($join) {
        $join->on('latest_eval.sales_order_id', '=', 'sales_orders.id')
             ->whereRaw('latest_eval.id = (SELECT MAX(id) FROM order_priority_evaluations WHERE sales_order_id = sales_orders.id)');
            })
            ->where('latest_eval.priority_level', 'bloquee')
            ->where('latest_eval.reason', 'like', '%Stock%')
            ->count();


        return response()->json([
            'by_priority_level' => $byLevel,
            'total_evaluated' => $byLevel->sum(),
            'blocked_amount_ttc' => round($blockedAmount, 2),
            'last_imports' => [
                'customers' => $lastImports['customers'] ?? null,
                'products' => $lastImports['products'] ?? null,
                'orders' => $lastImports['orders'] ?? null,
            ],
            'stock_alert' => [
            'has_alert' => $stockAlertCount > 0,
            'count' => $stockAlertCount,
            ],
        ]);
    }
}
