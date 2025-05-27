<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\StockMutationExport;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function exportStockMutations()
    {
        return Excel::download(new StockMutationExport(), 'Mutasi Stock.xlsx');
    }
}
