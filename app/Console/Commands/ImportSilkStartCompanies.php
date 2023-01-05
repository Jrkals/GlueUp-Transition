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

        $alreadyExists = [];
        $new           = [];
        $count         = 0;

        foreach ( $data as $row ) {
            $company = YcpCompany::getCompany( $row );
            if ( $company ) {
                $alreadyExists[] = $row;

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
        $this->line( 'Already Existing Companies: ' . sizeof( $alreadyExists ) );
        $this->line( 'New Companies: ' . sizeof( $new ) );


        $newWriter->writeSingleFileExcel( $new );
        $existingWriter->writeSingleFileExcel( $alreadyExists );

        return CommandAlias::SUCCESS;
    }
}
