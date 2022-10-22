<?php

namespace App\Helpers;

class CSVReader {
    private string $path;
    private $file;
    private array $data; // [ row => [key => value] ]

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->data = [];
    }

    public function extract_data(): array
    {
        $this->file = fopen($this->path, "r");
        if (!$this->file) {
            return [];
        }

        $headings = collect(fgetcsv($this->file))->map(function ($item, $key) {
            return str($item)->slug('_')->value();
        });

        $row_count = 0;
        while (($row = fgetcsv($this->file)) !== false) {
            if ($this->blankRow($row)) {
                continue;
            }
            for ($column = 0; $column < sizeof($row); $column++) {
                $this->data[$row_count][$headings[$column]] = $this->cleanValue($row[$column]);
            }
            $row_count++;
        }

        fclose($this->file);

        return $this->data;
    }

    private function cleanValue(string $str): string
    {
        if ($str === ' ' || $str === '#N/A') {
            return '';
        }
        return $str;
    }

    private function blankRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== '') {
                return false;
            }
        }
        return true;
    }
}
