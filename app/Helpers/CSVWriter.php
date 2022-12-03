<?php

namespace App\Helpers;

class CSVWriter {
    private string $path;
    private $file;
    private XLSXWriter $excel;

    public function __construct( string $path ) {
        $this->path  = $path;
        $this->excel = new XLSXWriter();
    }

    public function writeData( $data, array $header = [], $mode = 'a' ): void {
        if ( empty( $data ) ) {
            return;
        }
        $shouldPrintHeader = ! file_exists( $this->path ) || $mode === 'w';

        $this->file = fopen( $this->path, $mode );
        if ( ! $this->file ) {
            return;
        }

        if ( $shouldPrintHeader ) {
            $headings = ! empty( $header ) ? $header : array_keys( $data[0] );
            fputcsv( $this->file, $headings );
        }

        foreach ( $data as $row ) {
            if ( empty( $row ) || ! is_array( $row ) ) {
                continue;
            }
            fputcsv( $this->file, array_values( $row ) );
        }

        fclose( $this->file );
    }

    public function writeSingleFileExcel( $data ) {
        $this->writeSingleSheet( $data );
        $this->excel->writeToFile( $this->path );
    }

    public function writeDualSheetExcelFile( $data1, $data2 ) {
        $this->writeSingleSheet( $data1, 'Sheet1' );
        $this->writeSingleSheet( $data2, 'Sheet2' );
        $this->excel->writeToFile( $this->path );
    }

    private function writeSingleSheet( array $data, string $sheetName = 'Sheet' ) {
        $header = [];
        foreach ( array_keys( $data[0] ) as $key ) {
            $header[ $key ] = '@';
        }
        $this->excel->writeSheetHeader( $sheetName, $header );
        foreach ( $data as $row ) {
            $this->excel->writeSheetRow( $sheetName, array_values( $row ) );
        }
    }
}
