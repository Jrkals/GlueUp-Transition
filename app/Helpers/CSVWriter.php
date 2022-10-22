<?php

namespace App\Helpers;

class CSVWriter {
    private string $path;
    private $file;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function writeData($data, array $header = [], $mode = 'a'): void
    {
        if (empty($data)) {
            return;
        }
        $shouldPrintHeader = !file_exists($this->path) || $mode === 'w';

        $this->file = fopen($this->path, $mode);
        if (!$this->file) {
            return;
        }

        if ($shouldPrintHeader) {
            $headings = !empty($header) ? $header : array_keys($data[0]);
            fputcsv($this->file, $headings);
        }

        foreach ($data as $row) {
            if (empty($row) || !is_array($row)) {
                continue;
            }
            fputcsv($this->file, array_values($row));
        }

        fclose($this->file);
    }
}
