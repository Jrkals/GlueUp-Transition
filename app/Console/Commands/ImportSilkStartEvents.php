<?php

namespace App\Console\Commands;

use App\Helpers\ExcelWriter;
use App\Helpers\DirectoryReader;
use App\Helpers\Timer;
use App\Models\YcpContact;
use App\Models\YcpEvent;
use Illuminate\Console\Command;

class ImportSilkStartEvents extends Command {
    protected $signature = 'silkstart:importEvents {file}';

    protected $description = 'Imports SilkStart events to DB';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $timer = new Timer();
        $timer->start();
        $file = $this->argument( 'file' );

        $reader = new DirectoryReader( $file );
        $data   = $reader->readDataFromDirectory();
        $timer->elapsed( 'Read files' );

        $newWriter      = new ExcelWriter( './storage/app/exports/newEvents.xlsx' );
        $existingWriter = new ExcelWriter( './storage/app/exports/existingEvents.xlsx' );

        $alreadyExists = [];
        $new           = [];
        $count         = 0;
        $total         = sizeof( $data );

        foreach ( $data as $row ) {
            $found = YcpEvent::getEvent( $row );
            if ( $found ) {
                $alreadyExists[] = $row;
                $count ++;
                if ( $count % 1000 === 0 ) {
                    $this->line( $timer->progress( $count, $total ) );
                }
                $found->contacts()->save( YcpContact::getOrCreateContact( $row['attendee_name'], $row['email'] ),
                    [ 'attended' => $row['attended'] === 'True' ] );
                continue;
            }
            YcpEvent::fromCSV( $row );
            $new[] = $row;
            $count ++;
            if ( $count % 1000 === 0 ) {
                $this->line( $timer->progress( $count, $total ) );
            }
        }
        $this->line( 'New Events: ' . sizeof( $new ) );
        $this->line( 'Existing Events: ' . sizeof( $alreadyExists ) );

        $newWriter->writeSingleFileExcel( $new );
        $existingWriter->writeSingleFileExcel( $alreadyExists );

        return Command::SUCCESS;
    }
}
