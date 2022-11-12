<?php

namespace App\Helpers;

use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class DirectoryReader {

    private string $path;

    public function __construct( $dir ) {
        if ( ! is_dir( $dir ) ) {
            throw new FileNotFoundException( $dir );
        }
        $this->path = $dir;
    }

    public function readDataFromDirectory(): array {
        $files = scandir( $this->path );
        $data  = [];
        foreach ( $files as $file ) {
            if ( $file === '.' || $file === '..' ) {
                continue;
            }
            //   echo $file . "\n";
            $reader = new CSVReader( $this->path . '/' . $file );
            $data   = array_merge( $data, $reader->extract_data() );
        }

        return $data;
    }

}
