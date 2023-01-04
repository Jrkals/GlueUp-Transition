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

class ExportAll extends \Illuminate\Console\Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'glueup:exportAll';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs All GlueUp Exports. Takes about 8 minutes to run on a full DB';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $timer = new Timer();
        $timer->start();
        $this->line( 'Starting Company Export...' );
        Artisan::call( 'glueup:exportCompanies' );
        $this->line( $timer->elapsed( 'Done with company export' ) );
        Artisan::call( 'glueup:exportContacts' );
        $this->line( $timer->elapsed( 'Done with contacts export' ) );
        Artisan::call( 'glueup:exportMembers' );
        $this->line( $timer->elapsed( 'Done with member export' ) );
        $this->line( 'Done' );

        return Command::SUCCESS;
    }
}
