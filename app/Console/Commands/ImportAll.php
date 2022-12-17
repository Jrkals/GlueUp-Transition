<?php

namespace App\Console\Commands;

use App\Helpers\Timer;
use App\Models\Address;
use App\Models\Chapter;
use App\Models\EmailValidation;
use App\Models\Phone;
use App\Models\YcpCompany;
use App\Models\YcpContact;
use App\Models\YcpEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use PharIo\Manifest\Email;

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

        $contacts    = YcpContact::query()->count();
        $chapters    = Chapter::query()->count();
        $addresses   = Address::query()->count();
        $validEmails = EmailValidation::query()->where( 'valid', '=', true )->count();
        $phones      = Phone::query()->count();
        $companies   = YcpCompany::query()->count();
        $events      = YcpEvent::query()->count();

        $this->line( 'Imported ' . $contacts . ' contacts' );
        $this->line( 'Imported ' . $chapters . ' chapters' );
        $this->line( 'Imported ' . $addresses . ' addresses' );
        $this->line( 'Imported ' . $validEmails . ' valid emails' );
        $this->line( 'Imported ' . $phones . ' phones' );
        $this->line( 'Imported ' . $companies . ' companies' );
        $this->line( 'Imported ' . $events . ' events' );


        return Command::SUCCESS;
    }
}
