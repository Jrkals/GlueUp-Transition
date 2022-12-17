<?php

namespace App\Console\Commands;

use App\Helpers\Timer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ImportAll extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs All SilkStart Imports';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $contactsDir  = "/Users/justin/Documents/YCP/SS\ Exports/Contacts/Contact\ Info";
        $mailingDir   = "/Users/justin/Documents/YCP/SS\ Exports/Contacts/Mailing\ Addresses";
        $companiesDir = "/Users/justin/Documents/YCP/SS\ Exports/Companies/";
        $eventsDir    = "/Users/justin/Documents/YCP/SS\ Exports/Events/";
        $emailFile    = "/Users/justin/Documents/YCP/email\ verification.csv";
        $timer        = new Timer();
        $timer->start();
        $this->line( 'Starting Email Validation Import...' );
        Artisan::call( 'email:importValidation ' . $emailFile );
        $this->line( $timer->elapsed( 'Done with email validation' ) );
        Artisan::call( 'silkstart:importContacts ' . $contactsDir );
        $this->line( $timer->elapsed( 'Done with contacts import' ) );
        Artisan::call( 'silkstart:importContacts ' . $mailingDir );
        $this->line( $timer->elapsed( 'Done with contact address import' ) );
        Artisan::call( 'silkstart:importCompanies ' . $companiesDir );
        $this->line( $timer->elapsed( 'Done with companies import' ) );
        Artisan::call( 'silkstart:importEvents ' . $eventsDir );
        $this->line( $timer->elapsed( 'Done with event import' ) );
        $this->line( 'Done' );

        return Command::SUCCESS;
    }
}
