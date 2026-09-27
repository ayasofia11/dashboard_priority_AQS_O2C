<?php

namespace App\Services;

use App\Enums\ImportStatus;
use App\Models\{Customer, CustomerType, CustomerSoldeSnapshot, ImportBatch, ImportError};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\RawArrayImport;
use App\Services\Imports\AbstractImportService;

class CustomerImportService extends AbstractImportService
{
    private const COLUMN_MAP = [
        'customer_code' => 'Réf client',
        'name'          => 'Client',
        'type'          => 'Type',
        'distance_km'   => 'Distance AQS (km)',
        'solde'         => 'Solde Client',
    ];

    public function import(UploadedFile $file, ?int $userId): ImportBatch
    {
        $rows = Excel::toArray(new RawArrayImport, $file)[0];
        $header = array_shift($rows);

        $batch = ImportBatch::create([
            'source_type' => 'customers',
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


    private function resolveCustomerType(string $label): CustomerType
{
    $normalized = strtoupper($label);

    $code = match (true) {
        str_contains($normalized, 'IMPORT') && str_contains($normalized, 'EXPORT') => 'IMPORT_EXPORT',
        str_contains($normalized, 'DISTRIBUTEUR')    => 'DISTRIBUTEUR',
        str_contains($normalized, 'TRANSFORMATEUR')  => 'TRANSFORMATEUR',
        str_contains($normalized, 'UTILISATEUR')     => 'UTILISATEUR',
        default => null,
    };

    if (is_null($code)) {
        throw new \Exception("Type client non reconnu : '{$label}'.");
    }

    return CustomerType::where('code', $code)->firstOrFail();
}


    private function processRow(array $data): void
{
    $customerCode = trim((string) ($this->col($data, 'customer_code') ?? ''));
    $name = trim((string) ($this->col($data, 'name') ?? ''));
    $typeLabel = trim((string) ($this->col($data, 'type') ?? ''));

    $this->checkRequired([
            'Réf client' => $customerCode,
            'Client'     => $name,
            'Type'       => $typeLabel,
        ]);

    $distance = $this->col($data, 'distance_km');
    if (! is_null($distance) && (! is_numeric($distance) || $distance < 0)) {
        throw new \Exception("Distance invalide : '{$distance}'.");
    }

    $solde = $this->col($data, 'solde');
    if (! is_null($solde) && ! is_numeric($solde)) {
        throw new \Exception("Solde invalide : '{$solde}'.");
    }

    $type = $this->resolveCustomerType($typeLabel);

    $customer = Customer::updateOrCreate(
        ['customer_code' => $customerCode],
        ['name' => $name, 'customer_type_id' => $type->id, 'distance_km' => $distance]
    );

    if (! is_null($solde)) {
        CustomerSoldeSnapshot::create([
            'customer_id' => $customer->id,
            'outstanding_solde' => $solde,
            'snapshot_at' => now(),
        ]);
    }
}

    private function col(array $data, string $key): mixed
    {
        return $data[self::COLUMN_MAP[$key]] ?? null;
    }
}
