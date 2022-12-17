<?php

namespace App\Console\Commands;

use App\Helpers\CSVReader;
use App\Helpers\Timer;
use App\Models\EmailValidation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportEmailValidation extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:importValidation {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports an email validation report';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {

        $timer = new Timer();
        $timer->start();
        $reader = new CSVReader( $this->argument( 'file' ) );
        $data   = $reader->extract_data();
        $this->line( $timer->elapsed( 'read file' ) );
        $count = 0;

        $emailValidations = EmailValidation::query()->get();
        $this->line( 'Fetched data...' );
        $emailValidationArray = [];
        foreach ( $emailValidations as $validation ) {
            $emailValidationArray[ $validation->email ] = $validation->valid;
        }
        $this->line( 'Built array' );
        $toInsert = [];
        foreach ( $data as $row ) {
            $exists = isset( $emailValidationArray[ $row['email'] ] );
            if ( $exists ) {
                $count ++;
                if ( $count % 1000 === 0 ) {
                    $this->line( $timer->progress( $count, sizeof( $data ) ) );
                }
                continue;
            }
            $toInsert[] = [
                'email' => $row['email'],
                'valid' => $row['result'] === 'valid',
            ];
            $count ++;
            if ( $count % 1000 === 0 ) {
                $this->line( $timer->progress( $count, sizeof( $data ) ) );
            }
        }
        $this->line( 'Inserting into db...' );
        foreach ( array_chunk( $toInsert, 2000 ) as $data ) {
            EmailValidation::query()->insert( $data );
        }
        $this->line( 'Done' );

        return Command::SUCCESS;
    }
}
