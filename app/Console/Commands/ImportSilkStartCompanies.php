<?php

namespace App\Console\Commands;

use App\Helpers\CSVReader;
use App\Helpers\CSVWriter;
use App\Models\YcpCompany;
use App\Models\YcpContact;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ImportSilkStartCompanies extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'silkstart:importCompanies {file} {--dry}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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

        $newWriter      = new CSVWriter( './storage/app/exports/newCompanies.csv' );
        $existingWriter = new CSVWriter( './storage/app/exports/existingCompanies.csv' );
        $updatedWriter  = new CSVWriter( './storage/app/exports/updatedCompanies.csv' );


        $alreadyExists = [];
        $new           = [];
        $updated       = [];
        $count         = 0;

        foreach ( $data as $row ) {
            $company = YcpCompany::getCompany( $row );
            if ( $company ) {
                $alreadyExists[] = $row;

                $differences = YcpCompany::companyMatches( $row, $company );
                if ( $differences['any'] === true ) {
                    YcpCompany::updateCompany( $row, $company, $differences );
                    $updated[] = $row;
                }

                $count ++;
                continue;
            }
            if ( ! $dry ) {
                $ycpCompany = new YcpCompany();
                $ycpCompany->fromCSV( $row );
            }
            $new[] = $row;
            $count ++;
            if ( $count % 50 === 0 ) {
                $this->line( $count . ' Done. ' . ( sizeof( $data ) - $count ) . ' remaining' );
            }
        }
        $this->line( 'Already Exists: ' . sizeof( $alreadyExists ) );
        $this->line( 'New Imports: ' . sizeof( $new ) );
        $this->line( 'Updated: ' . sizeof( $updated ) );


        $newWriter->writeData( $new );
        $existingWriter->writeData( $alreadyExists );
        $updatedWriter->writeData( $updated );

        return CommandAlias::SUCCESS;
    }
}
