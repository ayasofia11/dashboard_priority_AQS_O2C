<?php

namespace App\Exports;

use App\Models\SalesOrder;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping};

class OrdersExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private array $filters) {}

    // Réutilise EXACTEMENT la même logique de filtres que SalesOrderController::index(),
    // pour que l'export corresponde toujours à ce que l'utilisateur voit à l'écran.
    public function query(): Builder
    {
        return SalesOrder::query()
            ->join('order_priority_evaluations as latest_eval', function ($join) {
                $join->on('latest_eval.sales_order_id', '=', 'sales_orders.id')
                     ->whereRaw('latest_eval.id = (SELECT MAX(id) FROM order_priority_evaluations WHERE sales_order_id = sales_orders.id)');
            })
            ->select('sales_orders.*', 'latest_eval.priority_level', 'latest_eval.final_score', 'latest_eval.reason')
            ->with('customer', 'items.product')

            ->when(!empty($this->filters['order_number']), fn ($q) =>
                $q->where('sales_orders.order_number', 'like', '%' . $this->filters['order_number'] . '%'))
            ->when(!empty($this->filters['client']), fn ($q) =>
                $q->whereHas('customer', fn ($q2) => $q2->where('name', 'like', '%' . $this->filters['client'] . '%')))
            ->when(!empty($this->filters['product_ref']), fn ($q) =>
                $q->whereHas('items.product', fn ($q2) => $q2->where('reference', 'like', '%' . $this->filters['product_ref'] . '%')))
            ->when(!empty($this->filters['priority']), fn ($q) =>
                $q->where('latest_eval.priority_level', $this->filters['priority']))
            ->when(!empty($this->filters['status']), fn ($q) =>
                $q->where('sales_orders.status', $this->filters['status']))
            ->when(!empty($this->filters['date_from']), fn ($q) =>
                $q->whereDate('sales_orders.order_date', '>=', $this->filters['date_from']))
            ->when(!empty($this->filters['date_to']), fn ($q) =>
                $q->whereDate('sales_orders.order_date', '<=', $this->filters['date_to']))

            ->orderByRaw("FIELD(latest_eval.priority_level, 'bloquee', 'critique', 'urgente', 'prioritaire', 'normale')");
    }

    public function headings(): array
    {
        return [
            'N° commande', 'Client', 'Date commande', 'Statut',
            'Qté commandée', 'Qté livrée', 'Reste à livrer', 'Montant TTC restant',
            'Priorité', 'Score', 'Raison (si bloquée)',
        ];
    }

    public function map($order): array
    {
        return [
            $order->order_number,
            $order->customer->name,
            $order->order_date->format('d/m/Y'),
            $order->status,
            $order->items->sum('ordered_quantity'),
            $order->items->sum('delivered_quantity'),
            $order->items->sum(fn ($i) => $i->remaining_quantity),
            $order->items->sum(fn ($i) => $i->total_amount),
            $order->priority_level,
            $order->final_score,
            $order->reason,
        ];
    }
}
