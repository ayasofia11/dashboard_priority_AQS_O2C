<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportFileRequest;
use App\Services\{CustomerImportService, ProductImportService, OrderImportService};
use Illuminate\Http\RedirectResponse;

class ImportController extends Controller
{
    public function create()
    {
        return view('imports.create');
    }

    public function customers(ImportFileRequest $request, CustomerImportService $service): RedirectResponse
    {
        $batch = $service->import($request->file('file'), auth()->id());

        return back()->with('status', "Import clients terminé : {$batch->success_rows} réussies, {$batch->error_rows} échouées.");
    }

    public function products(ImportFileRequest $request, ProductImportService $service): RedirectResponse
    {
        $batch = $service->import($request->file('file'), auth()->id());

        return back()->with('status', "Import produits terminé : {$batch->success_rows} réussies, {$batch->error_rows} échouées.");
    }

    public function orders(ImportFileRequest $request, OrderImportService $service): RedirectResponse
    {
        set_time_limit(120);

        $batch = $service->import($request->file('file'), auth()->id());

        return back()->with('status', "Import commandes terminé : {$batch->success_rows} réussies, {$batch->error_rows} échouées.");
    }
}
