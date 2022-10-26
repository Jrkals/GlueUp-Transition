<?php

namespace App\Console\Commands;

use App\Helpers\CSVReader;
use App\Helpers\CSVWriter;
use App\Helpers\GlueUp;
use App\Helpers\GlueUpCurl;
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

        $params  = array(
            "projection" => array(),
            "filter"     => array(
                array(
                    "projection" => "featured",
                    "operator"   => "eq",
                    "values"     => array( false )
                ),
                array(
                    "projection" => "emailAddress",
                    "operator"   => "lk",
                    "values"     => array( "@ozark" )
                ),
                // Advanced Search
                array(
                    "projection" => "phone",
                    "operator"   => "eq",
                    "values"     => array( "+86 15201516676" )
                ),
            ),
            "search"     => array(
                "fields"   => array( "givenName", "familyName" ),
                "value"    => "John",
                "fullText" => true
            ),
            "order"      => array(
                "familyName" => "asc"
            ),
            "offset"     => 0,
            "limit"      => 5
        );
        $g       = new GlueUp();
        $results = $g->post( 'membershipDirectory/members', $params );

        foreach ( $data as $row ) {
            if ( YCPContact::existsInDB( $row ) ) {
                $alreadyExists[] = $row;
                $count ++;
                continue;
            }
            if ( ! $dry ) {
                $ycpContact = new YCPContact();
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
