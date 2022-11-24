<?php

namespace App\Console\Commands;

use App\Helpers\CSVWriter;
use App\Helpers\DirectoryReader;
use App\Helpers\Timer;
use App\Models\YcpEvent;
use Illuminate\Console\Command;

class ImportSilkStartEvents extends Command {
    protected $signature = 'silkstart:importEvents {file} {--dry}';

    protected $description = 'Imports SilkStart events to DB';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $timer = new Timer();
        $timer->start();
        $dry  = $this->option( 'dry' );
        $file = $this->argument( 'file' );

        $reader = new DirectoryReader( $file );
        $data   = $reader->readDataFromDirectory();
        $timer->elapsed( 'Read files' );

        $newWriter      = new CSVWriter( './storage/app/exports/newEvents.csv' );
        $existingWriter = new CSVWriter( './storage/app/exports/existingEvents.csv' );

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
                continue;
            }
            YcpEvent::fromCSV( $row );
            $new[] = $row;
            $count ++;
            if ( $count % 1000 === 0 ) {
                $this->line( $timer->progress( $count, $total ) );
            }
        }

        $newWriter->writeData( $new, [], 'w' );
        $existingWriter->writeData( $alreadyExists, [], 'w' );

        return Command::SUCCESS;
    }
}