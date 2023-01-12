<?php

namespace App\Console\Commands;

use App\Helpers\ExcelWriter;
use App\Helpers\DirectoryReader;
use App\Helpers\Timer;
use App\Models\EmailValidation;
use App\Models\YcpContact;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ImportSilkStartContacts extends Command {

    protected $signature = 'silkstart:importContacts {file}';

    protected $description = 'Imports SilkStart contacts to DB';

    public function handle() {
        $timer = new Timer();
        $timer->start();
        $file = $this->argument( 'file' );

        $reader = new DirectoryReader( $file );
        $data   = $reader->readDataFromDirectory();
        $this->line( $timer->elapsed( 'Read files' ) );

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
                $count ++;
                if ( $count % 1000 === 0 ) {
                    $this->line( $timer->progress( $count, $total ) );
                }
                continue; // Skip these people
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
            $ycpContact = new YcpContact();
            $ycpContact->fromCSV( $row );
            $new[] = $row;
            $count ++;
            if ( $count % 1000 === 0 ) {
                $this->line( $timer->progress( $count, $total ) );
            }
        }
        //  $this->mergeDefunctChapterLeaders();

        $this->line( 'Already Existing Contacts: ' . sizeof( $alreadyExists ) );
        $this->line( 'New Contacts: ' . sizeof( $new ) );
        $this->line( 'Updated Contacts: ' . sizeof( $updated ) );

        $newWriter->writeSingleFileExcel( $new );
        $existingWriter->writeSingleFileExcel( $alreadyExists );
        $updatedWriter->writeSingleFileExcel( $updated );

        return CommandAlias::SUCCESS;
    }

    private function mergeDefunctChapterLeaders() {
        $this->line( 'merging defunct leaders...' );
        $timer = new Timer();
        $timer->start();
        $leadersByEmail   = YcpContact::query()->where( 'email', 'like', '%@ycp%' )->get();
        $count            = 0;
        $activeLeaders    = 0;
        $nonActiveLeaders = 0;
        foreach ( $leadersByEmail as $leader ) {
            if ( $leader->activeChapterLeader() ) {
                $activeLeaders ++;
                $count ++;
                if ( $count % 100 === 0 ) {
                    echo $timer->progress( $count, sizeof( $leadersByEmail ) ) . "\n";
                }
                continue;
            }
            //ignore non ycp emails for non-active leaders
            if ( ! str_contains( $leader->email, '@ycp' ) ) {
                continue;
            }
            $nonActiveLeaders ++;
            $matchingName = YcpContact::getDifferentEmailContact( [
                'email'        => $leader->email,
                'name'         => $leader->full_name,
                'home_chapter' => $leader->homeChapter()->name
            ] );
            if ( $matchingName ) {
                $matchingName->mergeIn( $leader );
            }
            $count ++;
            if ( $count % 100 === 0 ) {
                echo $timer->progress( $count, sizeof( $leadersByEmail ) ) . "\n";
            }
        }
        echo "Active Leaders " . $activeLeaders . "\nNon active leaders " . $nonActiveLeaders . "\n";
        echo $timer->elapsed( 'Done with merging leaders' ) . "\n";
    }
}
