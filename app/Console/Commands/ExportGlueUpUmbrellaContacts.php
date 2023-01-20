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
        $contacts = YcpContact::query()
                              ->with( 'address' )
                              ->whereHas( 'plans' )
                              ->get( [
                                  'id',
                                  'first_name',
                                  'last_name',
                                  'email',
                              ] );
        $this->line( $timer->elapsed( 'Contacts Fetched' ) );

        $data  = [];
        $count = 0;

        $total = sizeof( $contacts );
        $this->line( 'exporting ' . sizeof( $contacts ) );
        $writer = new ExcelWriter( './storage/app/exports/contacts/umbrella.xlsx' );
        foreach ( $contacts as $contact ) {
            $address = $contact->billingAddress();
            if ( ! $contact->email
                 || $contact->subscribed === 'Unsubscribed'
                 || ! $address ) {
                continue;
            }

            $row['First Name']     = $contact->first_name ?? '';
            $row['Last Name']      = $contact->last_name ?? '';
            $row['Email']          = $contact->email;
            $row['Address']        = $address->street1;
            $row['City']           = $address->city;
            $row['State']          = $address->state;
            $row['Postal Code']    = $address->postal_code;
            $row['Country/Region'] = $address->country;

//            $row['Chapter Interest List']   = StringHelpers::mapChapterInterestList( $contact->chapter_interest_list );
//            $row['Spiritual Assessment']    = StringHelpers::glueUpSlugify( $contact->spiritual_assessment );
//            $row['Professional Assessment'] = StringHelpers::glueUpSlugify( $contact->professional_assessment );


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
