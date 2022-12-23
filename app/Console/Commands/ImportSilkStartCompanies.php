<?php

namespace App\Console\Commands;

use App\Helpers\CSVReader;
use App\Helpers\ExcelWriter;
use App\Helpers\DirectoryReader;
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
    protected $signature = 'silkstart:importCompanies {file}';

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
        $file   = $this->argument( 'file' );
        $reader = new DirectoryReader( $file );
        $data   = $reader->readDataFromDirectory();

        $newWriter      = new ExcelWriter( './storage/app/exports/newCompanies.xlsx' );
        $existingWriter = new ExcelWriter( './storage/app/exports/existingCompanies.xlsx' );
        $updatedWriter  = new ExcelWriter( './storage/app/exports/updatedCompanies.xlsx' );

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
                if ( $count % 500 === 0 ) {
                    $this->line( $count . ' Done. ' . ( sizeof( $data ) - $count ) . ' remaining' );
                }
                continue;
            }
            $ycpCompany = new YcpCompany();
            $ycpCompany->fromCSV( $row );

            $new[] = $row;
            $count ++;
            if ( $count % 500 === 0 ) {
                $this->line( $count . ' Done. ' . ( sizeof( $data ) - $count ) . ' remaining' );
            }
        }
        $this->line( 'Already Exists: ' . sizeof( $alreadyExists ) );
        $this->line( 'New Imports: ' . sizeof( $new ) );
        $this->line( 'Updated: ' . sizeof( $updated ) );


        $newWriter->writeSingleFileExcel( $new );
        $existingWriter->writeSingleFileExcel( $alreadyExists );
        $updatedWriter->writeSingleFileExcel( $updated );

        return CommandAlias::SUCCESS;
    }
}
