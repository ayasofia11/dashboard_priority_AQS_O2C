<?php
namespace App\Services\Imports;

abstract class AbstractImportService
{
    protected function checkRequired(array $fields): void
    {
        $missing = array_keys(array_filter($fields, fn ($v) => empty($v)));

        if (! empty($missing)) {
            throw new \Exception('Colonne(s) obligatoire(s) manquante(s) : ' . implode(', ', $missing) . '.');
        }
    }
}
