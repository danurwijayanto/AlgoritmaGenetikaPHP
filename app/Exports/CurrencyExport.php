<?php

namespace App\Exports;

use App\Currency;
use Maatwebsite\Excel\Concerns\FromCollection;

class CurrencyExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Currency::all();
    }
}
