<?php

namespace App\Console\Commands;

use App\Helpers\ExcelWriter;
use App\Models\YcpContact;
use Illuminate\Console\Command;

class CreateUnsubsFile extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'silkstart:makeUnsubsFile';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CSV of unsubs from db';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $unsubs    = YcpContact::query()->where( 'subscribed', '=', 'Unsubscribed' )
                               ->get( [ 'full_name', 'email' ] )->toArray();
        $csvWriter = new ExcelWriter( './storage/app/exports/unsubscribes.csv' );
        $csvWriter->writeSingleFileExcel( $unsubs );

        return Command::SUCCESS;
    }
}
