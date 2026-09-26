<?php

namespace App\Http\Controllers;

use App\Exports\OrdersExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function export(Request $request)
    {
        return Excel::download(
            new OrdersExport($request->all()),
            'commandes_priorisees_' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }
}
