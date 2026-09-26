<?php

namespace App\Services;

use App\Enums\ImportStatus;
use App\Models\{Customer, Product, SalesOrder, SalesOrderItem, ImportBatch, ImportError};
use App\Services\PriorityService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\RawArrayImport;
use App\Services\Imports\AbstractImportService;
use Carbon\Carbon;

class OrderImportService extends AbstractImportService
{
    private const COLUMN_MAP = [
        'order_number'  => 'N° commande',
        'line_number'   => 'Ligne',
        'order_date'    => 'Date commande',
        'customer_name' => 'Client',
        'product_ref'   => 'Réf. produit',
        'unit_price'    => 'Prix unitaire',
        'tva_rate'      => 'TVA %',
        'ordered_qty'   => 'Qté commandée',
        'delivered_qty' => 'Qté déjà livrée',
        'initial_qty'   => 'Qte livree initiale',
        'statut'        => 'Statut',
        'observation'   => 'Observation',
    ];

    public function __construct(private PriorityService $priorityService) {}

    public function import(UploadedFile $file, ?int $userId): ImportBatch
    {
        $rows = Excel::toArray(new RawArrayImport, $file)[0];
        $header = array_shift($rows);

        $batch = ImportBatch::create([
            'source_type' => 'orders',
            'file_name' => $file->getClientOriginalName(),
            'imported_by' => $userId,
            'total_rows' => count($rows),
            'status' => ImportStatus::Processing,
        ]);

        $touchedOrderIds = [];

        foreach ($rows as $i => $row) {
            try {
                DB::transaction(function () use ($header, $row, $batch, &$touchedOrderIds) {
                    $data = array_combine($header, $row);
                    $order = $this->processRow($data, $batch->id);
                    $touchedOrderIds[] = $order->id;
                });
                $batch->increment('success_rows');
            } catch (\Throwable $e) {
                ImportError::create([
                    'batch_id' => $batch->id,
                    'row_number' => $i + 2,
                    'error_message' => $e->getMessage(),
                ]);
                $batch->increment('error_rows');
            }
        }

        // Statut et priorité recalculés une fois par commande touchée, pas par ligne.
        $this->priorityService->preparePopulations();

        foreach (array_unique($touchedOrderIds) as $orderId) {
            $order = SalesOrder::find($orderId);
            $order->recalculateStatus();
            $this->priorityService->evaluate($order->fresh(['items.product.productType', 'customer.customerType']));
        }

        $batch->update(['status' => ImportStatus::Completed, 'imported_at' => now()]);

        return $batch;
    }

    private function processRow(array $data, int $batchId): SalesOrder
    {
        $orderNumber = trim((string) ($this->col($data, 'order_number') ?? ''));
        $lineNumber = $this->col($data, 'line_number');
        $customerName = trim((string) ($this->col($data, 'customer_name') ?? ''));
        $productRef = trim((string) ($this->col($data, 'product_ref') ?? ''));
        $orderDateRaw = $this->col($data, 'order_date');
        $orderedQtyRaw = $this->col($data, 'ordered_qty');

        $this->checkRequired([
    'N° commande'     => $orderNumber,
    'Ligne'            => $lineNumber,
    'Client'           => $customerName,
    'Réf. produit'     => $productRef,
    'Date commande'    => $orderDateRaw,
    'Qté commandée'    => $orderedQtyRaw,
]);

        if (! is_numeric($orderedQtyRaw) || $orderedQtyRaw <= 0) {
            throw new \Exception("Qté commandée invalide : '{$orderedQtyRaw}'.");
        }

        $deliveredQtyRaw = $this->col($data, 'delivered_qty') ?? 0;
        if (! is_numeric($deliveredQtyRaw) || $deliveredQtyRaw < 0) {
            throw new \Exception("Qté déjà livrée invalide : '{$deliveredQtyRaw}'.");
        }

        $orderedQty = (float) $orderedQtyRaw;
        $deliveredQty = (float) $deliveredQtyRaw;

        if ($deliveredQty > $orderedQty) {
            throw new \Exception("Qté déjà livrée ({$deliveredQty}) supérieure à la qté commandée ({$orderedQty}).");
        }

        // Client et produit doivent déjà exister (fichiers clients/produits importés avant).
        $customer = Customer::where('name', $customerName)->first();
        if (! $customer) {
            throw new \Exception("Client introuvable : '{$customerName}'. Importez d'abord le fichier clients.");
        }

        $product = Product::where('reference', $productRef)->first();
        if (! $product) {
            throw new \Exception("Produit introuvable : '{$productRef}'. Importez d'abord le fichier produits.");
        }

        $order = SalesOrder::updateOrCreate(
            ['order_number' => $orderNumber],
            [
                'customer_id' => $customer->id,
                'order_date' => $this->excelDateToCarbon($orderDateRaw),
                'last_import_batch_id' => $batchId,
            ]
        );

        SalesOrderItem::updateOrCreate(
            ['sales_order_id' => $order->id, 'line_number' => (int) $lineNumber],
            [
                'product_id' => $product->id,
                'unit_price' => $this->col($data, 'unit_price'),
                'tva_rate' => $this->normalizeTvaRate($this->col($data, 'tva_rate')),
                'ordered_quantity' => $orderedQty,
                'delivered_quantity' => $deliveredQty,
                'initial_delivered_quantity' => $this->col($data, 'initial_qty') ?? 0,
                'status' => $this->mapStatus($this->col($data, 'statut')),
                'observation' => $this->col($data, 'observation'),
            ]
        );

        return $order;
    }


    // Gère les deux formats vus dans tes fichiers : "0.19" (commandes) et "19%" (produits).
    private function normalizeTvaRate($value): float
    {
        if (is_null($value)) return 0.19;
        if (is_string($value) && str_contains($value, '%')) {
            return (float) str_replace('%', '', $value) / 100;
        }
        return (float) $value;
    }

    private function mapStatus(?string $label): string
    {
        $label = trim((string) $label);
        return match ($label) {
            'Livré' => 'DELIVERED',
            'Partiellement livré' => 'PARTIAL',
            'En cours' => 'OPEN',
            default => throw new \Exception("Statut de ligne non reconnu : '{$label}'."),
        };
    }

    private function col(array $data, string $key): mixed
    {
        return $data[self::COLUMN_MAP[$key]] ?? null;
    }

    private function excelDateToCarbon($value): ?Carbon
    {
        if (empty($value)) return null;
        return is_numeric($value)
            ? Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))
            : Carbon::parse($value);
    }
}
