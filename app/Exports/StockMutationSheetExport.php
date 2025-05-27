<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Setting;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockMutationSheetExport implements WithTitle, FromView, WithStyles
{
    protected $category;
    protected $stockMutations;

    public function __construct(string $category, Collection $stockMutations)
    {
        $this->category = $category;
        $this->stockMutations = $stockMutations;
    }

        /**
     * @return string
     */
    public function title(): string
    {
        return $this->category;
    }

    /**
     * @return View
     */
    public function view(): View
    {
        $sessionFilters = session('filter_stock_mutation');

        $dates = $sessionFilters['dates'];
        [$start, $end] = explode(' - ', $dates);
        $startDate = Carbon::createFromFormat('d/m/Y', $start)->translatedFormat('d F Y');
        $endDate = Carbon::createFromFormat('d/m/Y', $end)->translatedFormat('d F Y');

        if ($this->category == 'accessories') {
            $categoryIdn = 'Aksesoris';
        } else if ($this->category == 'main_material'){
            $categoryIdn =  'Material Utama';
        } else {
            $categoryIdn = 'Aset';
        }

        return view('exports.stock-mutation', [
            'setting' => Setting::first(),
            'warehouse' => Warehouse::find($sessionFilters['warehouse_id']),
            'dates' => $startDate . ' - ' . $endDate,
            'categoryIdn' => $categoryIdn,
            'stockMutations' => $this->stockMutations
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        $style = [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ],
                'font' => ['bold' => true, 'size' => 14, 'name' => 'Arial'],
            ];

        return [
            1 => $style,
            2 => $style,
            3 => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ],
                'font' => ['bold' => true, 'size' => 12, 'name' => 'Arial'],
            ]
        ];
    }



}
