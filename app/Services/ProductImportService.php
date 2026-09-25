<?php

namespace App\Services;

use App\Enums\ImportStatus;
use App\Models\{Product, ProductType, StockSnapshot, ImportBatch, ImportError};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\RawArrayImport;
use App\Services\Imports\AbstractImportService;

class ProductImportService extends AbstractImportService
{
    private const COLUMN_MAP = [
        'reference'     => 'Réf. produit',
        'name'          => 'Produit',
        'unit'          => 'Unité',
        'type'          => 'Type produit',
        'available_qty' => 'Qte disponible',
    ];

    public function import(UploadedFile $file, ?int $userId): ImportBatch
    {
        $rows = Excel::toArray(new RawArrayImport, $file)[0];
        $header = array_shift($rows);

        $batch = ImportBatch::create([
            'source_type' => 'products',
            'file_name' => $file->getClientOriginalName(),
            'imported_by' => $userId,
            'total_rows' => count($rows),
            'status' => ImportStatus::Processing,
        ]);

        foreach ($rows as $i => $row) {
            try {
                DB::transaction(function () use ($header, $row) {
                    $data = array_combine($header, $row);
                    $this->processRow($data);
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

        $batch->update(['status' => ImportStatus::Completed, 'imported_at' => now()]);

        return $batch;
    }

    private function processRow(array $data): void
    {
        $reference = trim((string) ($this->col($data, 'reference') ?? ''));
        $name = trim((string) ($this->col($data, 'name') ?? ''));
        $typeLabel = trim((string) ($this->col($data, 'type') ?? ''));

        $this->checkRequired([
            'Réf. produit' => $reference,
            'Produit'      => $name,
            'Type produit' => $typeLabel,
        ]);

        $availableQty = $this->col($data, 'available_qty');

        // Cellule vide/null -> rejetée explicitement (pas de valeur par défaut devinée)
        if ($availableQty === null || $availableQty === '') {
            throw new \Exception("Quantité disponible manquante pour le produit '{$reference}'.");
        }

        if (! is_numeric($availableQty) || $availableQty < 0) {
            throw new \Exception("Quantité disponible invalide : '{$availableQty}'.");
        }

        $type = $this->resolveProductType($typeLabel);

        $product = Product::updateOrCreate(
            ['reference' => $reference],
            [
                'name' => $name,
                'unit' => trim((string) ($this->col($data, 'unit') ?? 'unité')),
                'product_type_id' => $type->id,
            ]
        );

        StockSnapshot::create([
            'product_id' => $product->id,
            'available_qty' => $availableQty,
            'snapshot_at' => now(),
        ]);
    }

    // Le "SEMI" doit être vérifié AVANT "FINI", sinon "Semi fini" matcherait aussi "FINI"
    // à cause de str_contains, ce qui donnerait le mauvais type.
    private function resolveProductType(string $label): ProductType
    {
        $normalized = strtoupper($label);

        dump("Fichier: '{$label}' → normalisé: '{$normalized}'");

        $code = match (true) {
            str_contains($normalized, 'SEMI') => 'SEMI_FINI',
            str_contains($normalized, 'FINI') => 'FINI',
            default => null,
        };

        if (is_null($code)) {
            throw new \Exception("Type produit non reconnu : '{$label}'.");
        }

        return ProductType::where('code', $code)->firstOrFail();
    }

    private function col(array $data, string $key): mixed
    {
        return $data[self::COLUMN_MAP[$key]] ?? null;
    }
}
