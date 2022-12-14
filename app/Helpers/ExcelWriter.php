<?php

namespace App\Helpers;

class ExcelWriter {
    private string $path;
    private XLSXWriter $excel;

    public function __construct( string $path ) {
        $this->path  = $path;
        $this->excel = new XLSXWriter();
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
        if ( empty( $data ) ) {
            return;
        }
        foreach ( array_keys( $data[0] ) as $key ) {
            $typeString = '@'; //string
            if ( str_contains( strtolower( $key ), 'date' ) ) {
                $typeString = 'MM/DD/YY'; //Date
            }
            $header[ $key ] = $typeString;
        }
        $this->excel->writeSheetHeader( $sheetName, $header );
        foreach ( $data as $row ) {
            $this->excel->writeSheetRow( $sheetName, array_values( $row ) );
        }
    }
}
