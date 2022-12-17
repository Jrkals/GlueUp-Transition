<?php

namespace App\Console\Commands;

use App\Helpers\ExcelWriter;
use App\Helpers\Timer;
use App\Models\YcpContact;
use Illuminate\Console\Command;

class ExportContactEmails extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports emails so they can be verified';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $timer = new Timer();
        $timer->start();
        $csvWriter = new ExcelWriter( './storage/app/exports/emailsForVerification.xlsx' );

        $leaderUnsubs = YcpContact::query()
                                  ->with( 'plans' )
                                  ->where( 'subscribed', '=', 'Unsubscribed' )
                                  ->whereHas( 'plans' )
                                  ->get();
        $this->line( 'Leader unsubs ' . sizeof( $leaderUnsubs ) );
        $emails = YcpContact::query()->where( 'subscribed', '!=', 'Unsubscribed' )
                            ->where( 'email', '!=', '' )
                            ->get( 'email' )->toArray();
        $this->line( $timer->elapsed( 'fetched data' ) );

        $this->line( 'Writing ' . sizeof( $emails ) . ' emails' );

        $csvWriter->writeSingleFileExcel( $emails );

        $this->line( $timer->elapsed( 'wrote emails to file' ) );

        return Command::SUCCESS;
    }
}
