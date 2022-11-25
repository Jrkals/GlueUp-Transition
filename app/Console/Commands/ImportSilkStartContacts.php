<?php

namespace App\Console\Commands;

use App\Helpers\CSVReader;
use App\Helpers\CSVWriter;
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

        $newWriter      = new CSVWriter( './storage/app/exports/new.csv' );
        $existingWriter = new CSVWriter( './storage/app/exports/existing.csv' );
        $updatedWriter  = new CSVWriter( './storage/app/exports/updated.csv' );

        $alreadyExists = [];
        $new           = [];
        $updated       = [];
        $count         = 0;
        $total         = sizeof( $data );

        $contactsMemo = new Memo();
        $contactsMemo->formContactsByEmail();

        foreach ( $data as $row ) {
            //treat bad emails as no email at all.
            if ( ! EmailValidation::emailIsValid( $row['email'] ) ) {
                unset( $row['email'] );
            }

            //search by email first
            $found = $contactsMemo->findContactByEmail( $row['email'] );
            //then by name
            if ( ! $found ) {
                $found = YcpContact::getContact( $row );
            }
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
                $contactsMemo->appendContact( $ycpContact );
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

        $newWriter->writeData( $new );
        $existingWriter->writeData( $alreadyExists );
        $updatedWriter->writeData( $updated );

        return CommandAlias::SUCCESS;
    }
}
