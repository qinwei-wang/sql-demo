<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

class SQLResultsExport implements FromCollection
{
    protected $results;

    public function __construct($results)
    {
        $this->results = $results;
    }

    public function collection()
    {
        return collect($this->results);
    }
}
