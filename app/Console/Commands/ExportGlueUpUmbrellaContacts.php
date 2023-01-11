<?php

namespace App\Console\Commands;

use App\Helpers\ExcelWriter;
use App\Helpers\StringHelpers;
use App\Helpers\Timer;
use App\Models\YcpContact;
use Illuminate\Console\Command;

class ExportGlueUpUmbrellaContacts extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'glueup:exportUmbrella';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export Umbrella contacts with a few fields';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $timer = new Timer();
        $timer->start();
        $this->line( 'running query...' );
        $contacts = YcpContact::query()->where( 'subscribed', '=', 'Unsubscribed' )->get( [
            'first_name',
            'last_name',
            'email'
        ] );
        $this->line( $timer->elapsed( 'Contacts Fetched' ) );

        $data  = [];
        $count = 0;

        $total = sizeof( $contacts );
        $this->line( 'exporting ' . sizeof( $contacts ) );
        $writer = new ExcelWriter( './storage/app/exports/contacts/umbrella.xlsx' );
        foreach ( $contacts as $contact ) {
            if ( ! $contact->email /*|| $contact->status !== 'Unsubscribed'*/ ) {
                continue;
            }

            $row['First Name'] = $contact->first_name ?? '';
            $row['Last Name']  = $contact->last_name ?? '';
            $row['Email']      = $contact->email;
            //  $row['Chapter Interest List'] = StringHelpers::glueUpSlugify( $contact->chapter_interest_list );
            $row['Bio'] = 'UNSUBSCRIBE DELETE';

            $data[] = $row;
            $count ++;
            if ( $count % 1000 === 0 ) {
                $this->line( $timer->progress( $count, $total ) );
            }
        }
        $writer->writeSingleFileExcel( $data );

        return Command::SUCCESS;
    }
}
