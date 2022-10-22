<?php

namespace App\Console\Commands;

use App\Helpers\CSVReader;
use App\Helpers\CSVWriter;
use App\Models\YCPContact;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ImportSilkStartContacts extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'silkstart:importContacts {file} {--dry}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports SilkStart contacts to DB';

    /**
     * Execute the console command.
     *
     * @return int
     */
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
            if ( YCPContact::existsInDB( $row ) ) {
                $alreadyExists[] = $row;
                continue;
            }
            if ( ! $dry ) {
                $ycpContact = new YCPContact();
                $ycpContact->fromCSV( $row );
            }
            $new[] = $row;

        }
        $this->line( 'Already Exists: ' . sizeof( $alreadyExists ) );
        $this->line( 'New Imports: ' . sizeof( $new ) );

        $newWriter->writeData( $new );
        $existingWriter->writeData( $alreadyExists );

        return CommandAlias::SUCCESS;
    }
}
