<?php

namespace Waka\Utils\Classes\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMappedCells;

class ImportModelController implements WithMultipleSheets
{
    public $model;
    public $data;
    public $config;

    public function __construct($model)
    {
        $this->model = $model;
        
    }
    public function sheets(): array
    {
        $config = $this->model.'_config';
        $data = $this->model.'_data';

        return [
            $config => $this->config = new configImport(),
            $data => $this->data = new dataImport(),
        ];
    }
}

class ConfigImport implements ToCollection {

    public $data;

    public function collection(Collection $rows)
    {
        $this->data = [];
        foreach ($rows as $row) {
            $this->data[$row[0]] = $row[1];
        }
        return $this->data;
    }

}
class DataImport implements ToCollection, WithHeadingRow {
    
    public $data;

    public function collection(Collection $rows)
    {
        $this->data = [];
        foreach ($rows as $row) {
            $obj = [
            'var' => $row['var'] ?? null,
            'name' => $row['nom'] ?? null,
            'type' => $row['type'] ?? null,
            'column' => $row['colonne'] ?? null,
            'nullable' => $row['est_null'] ?? null,
            'field' => $row['champ'] ?? null,
            'required' => $row['requis'] ?? null,
            'title' => $row['titre'] ?? null,
            'append' => $row['append'] ?? null,
            'json' => $row['json'] ?? null,
            'getter' =>  $row['getter'] ?? null,
            ];
            array_push($this->data, $obj);

        }
        return $this->data;
    }

}