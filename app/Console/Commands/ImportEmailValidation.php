<?php

namespace App\Console\Commands;

use App\Helpers\CSVReader;
use App\Models\EmailValidation;
use Illuminate\Console\Command;

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
        $reader = new CSVReader( $this->argument( 'file' ) );
        $data   = $reader->extract_data();

        foreach ( $data as $row ) {
            if ( EmailValidation::query()->find( $row['email_in'] ) ) {
                continue;
            }
            $validation = new EmailValidation( [
                'email' => $row['email_in'],
                'valid' => $row['result'] === 'valid',
            ] );
            $validation->save();
        }

        return Command::SUCCESS;
    }
}
