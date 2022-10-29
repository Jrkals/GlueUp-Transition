<?php

namespace App\Console\Commands;

use App\Helpers\CSVReader;
use App\Helpers\CSVWriter;
use App\Helpers\GlueUp;
use App\Helpers\GlueUpCurl;
use App\Models\YcpContact;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ImportSilkStartContacts extends Command {

    protected $signature = 'silkstart:importContacts {file} {--dry}';

    protected $description = 'Imports SilkStart contacts to DB';

    public function handle() {
        $dry    = $this->option( 'dry' );
        $file   = $this->argument( 'file' );
        $reader = new CSVReader( $file );
        $data   = $reader->extract_data();

        $newWriter      = new CSVWriter( './storage/app/exports/new.csv' );
        $existingWriter = new CSVWriter( './storage/app/exports/existing.csv' );

        $alreadyExists = [];
        $new           = [];
        $count         = 0;

        foreach ( $data as $row ) {
            if ( YcpContact::existsInDB( $row ) ) {
                $alreadyExists[] = $row;
                $count ++;
                continue;
            }
            if ( ! $dry ) {
                $ycpContact = new YcpContact();
                $ycpContact->fromCSV( $row );
            }
            $new[] = $row;
            $count ++;
            if ( $count % 50 === 0 ) {
                $this->line( $count . ' Done. ' . ( sizeof( $data ) - $count ) . ' remaining' );
            }
        }
        $this->line( 'Already Exists: ' . sizeof( $alreadyExists ) );
        $this->line( 'New Imports: ' . sizeof( $new ) );

        $newWriter->writeData( $new );
        $existingWriter->writeData( $alreadyExists );

        return CommandAlias::SUCCESS;
    }
}
