<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use Illuminate\Http\Request;

class ImportHistoryController extends Controller
{
    // Liste tous les imports, avec filtres.
    public function index(Request $request)
    {
        $batches = ImportBatch::query()
            ->with('importer')
            ->when($request->filled('source_type'), fn ($q) =>
                $q->where('source_type', $request->source_type))
            ->when($request->filled('status'), fn ($q) =>
                $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) =>
                $q->whereDate('imported_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) =>
                $q->whereDate('imported_at', '<=', $request->date_to))
            ->orderByDesc('imported_at')
            ->paginate(20)
            ->withQueryString();

        return response()->json($batches);
    }

    // Détail d'un import : résumé + toutes ses erreurs.
    public function show(ImportBatch $batch)
    {
        $batch->load('importer', 'errors');

        return response()->json([
            'id' => $batch->id,
            'source_type' => $batch->source_type,
            'file_name' => $batch->file_name,
            'imported_by' => $batch->importer?->name,
            'imported_at' => $batch->imported_at,
            'status' => $batch->status,
            'total_rows' => $batch->total_rows,
            'success_rows' => $batch->success_rows,
            'error_rows' => $batch->error_rows,
            'errors' => $batch->errors->map(fn ($e) => [
                'row_number' => $e->row_number,
                'column_name' => $e->column_name,
                'error_message' => $e->error_message,
            ]),
        ]);
    }

    // Seulement les erreurs d'un import (utile si la liste des erreurs est longue).
    public function errors(ImportBatch $batch)
    {
        return response()->json(
            $batch->errors()->orderBy('row_number')->paginate(50)
        );
    }
}
