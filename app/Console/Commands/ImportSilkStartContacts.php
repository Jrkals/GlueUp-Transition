<?php

namespace App\Console\Commands;

use App\Helpers\CSVReader;
use App\Helpers\ExcelWriter;
use App\Helpers\DirectoryReader;
use App\Helpers\Memo;
use App\Helpers\Timer;
use App\Models\EmailValidation;
use App\Models\YcpContact;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ImportSilkStartContacts extends Command {

    protected $signature = 'silkstart:importContacts {file} {--dry}';

    protected $description = 'Imports SilkStart contacts to DB';

    public function handle() {
        $timer = new Timer();
        $timer->start();
        $dry  = $this->option( 'dry' );
        $file = $this->argument( 'file' );

        $reader = new DirectoryReader( $file );
        $data   = $reader->readDataFromDirectory();
        $timer->elapsed( 'Read files' );

        $newWriter      = new ExcelWriter( './storage/app/exports/new.xlsx' );
        $existingWriter = new ExcelWriter( './storage/app/exports/existing.xlsx' );
        $updatedWriter  = new ExcelWriter( './storage/app/exports/updated.xlsx' );

        $alreadyExists = [];
        $new           = [];
        $updated       = [];
        $count         = 0;
        $total         = sizeof( $data );

        foreach ( $data as $row ) {
            //treat bad emails as no email at all.
            if ( ! EmailValidation::emailIsValid( $row['email'] ) ) {
                unset( $row['email'] );
            }

            $found = YcpContact::getContact( $row );

            if ( $found ) {
                $alreadyExists[] = $row;
                $differences     = YcpContact::contactsMatch( $row, $found );
                if ( $differences['any'] === true ) {
                    YcpContact::updateContact( $row, $found, $differences );
                    $updated[] = $row;
                }
                $count ++;
                if ( $count % 1000 === 0 ) {
                    $this->line( $timer->progress( $count, $total ) );
                }
                continue;
            }
            if ( ! $dry ) {
                $ycpContact = new YcpContact();
                $ycpContact->fromCSV( $row );
            }
            $new[] = $row;
            $count ++;
            if ( $count % 1000 === 0 ) {
                $this->line( $timer->progress( $count, $total ) );
            }
        }
        $this->line( 'Already Exists: ' . sizeof( $alreadyExists ) );
        $this->line( 'New Imports: ' . sizeof( $new ) );
        $this->line( 'Updated ' . sizeof( $updated ) );

        $newWriter->writeSingleFileExcel( $new );
        $existingWriter->writeSingleFileExcel( $alreadyExists );
        $updatedWriter->writeSingleFileExcel( $updated );

        return CommandAlias::SUCCESS;
    }
}
