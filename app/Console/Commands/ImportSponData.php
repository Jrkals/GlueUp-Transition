<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportSponData extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spon:importContacts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports SPON Contacts and creates csvs of new people';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {

        return Command::SUCCESS;
    }
}
