<?php

namespace App\Console\Commands;

use App\Helpers\ExcelWriter;
use App\Helpers\StringHelpers;
use App\Helpers\Timer;
use App\Models\YcpContact;
use Illuminate\Console\Command;

class ExportGlueUpContacts extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'glueup:exportContacts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export Contact Info to CSV';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $this->line( 'exporing csv for Contacts ' );
        $writer = new ExcelWriter( './storage/app/exports/contacts/contacts.xlsx' );
        $data   = [];
        $timer  = new Timer();
        $timer->start();
        $this->line( 'running query...' );
        $contacts = YcpContact::query()->with( [
            'plans',
            'chapters',
            'companies',
            'phones',
            'events',
        ] )->get()->take( 5000 );
        $this->line( $timer->elapsed( 'Contacts Fetched' ) );
        $count = 0;

        $total = sizeof( $contacts );
        foreach ( $contacts as $contact ) {
            if ( ! $contact->email ) {
                echo $contact->full_name . " missing email\n";
                continue;
            }
            $address = $contact->address;
            $phone   = $contact->primaryPhone();

            $homeChapter                       = $contact->homeChapter()?->glueupId() ?? '';
            $chapters                          = $contact->chapterIds();
            $companyName                       = $contact->companyName();
            $row['First Name']                 = $contact->first_name ?? '';
            $row['Last Name']                  = $contact->last_name ?? '';
            $row['Address']                    = $address->street1 ?? '';
            $row['Address 2']                  = $address->street2 ?? '';
            $row['City']                       = $address->city ?? '';
            $row['State']                      = $address->state ?? '';
            $row['Postal Code']                = $address->postal_code ?? '';
            $row['Company']                    = $companyName;
            $row['Primary Chapter']            = $homeChapter;
            $row['Chapters']                   = $chapters;
            $row['Phone']                      = $phone?->number;
            $row['Email']                      = $contact->email;
            $row['Job Title']                  = $contact->title ?? '';
            $row['Date of Birth']              = $contact->birthday ?? '';
            $row['Email Status']               = $contact->subscribed;
            $row['Spiritual Assessment']       = StringHelpers::glueUpSlugify( $contact->spiritual_assessment ) ?? '';
            $row['Professional Assessment']    = StringHelpers::glueUpSlugify( $contact->professional_assessment ) ?? '';
            $row['T Shirt Size']               = StringHelpers::glueUpSlugify( $contact->t_shirt_size ) ?? '';
            $row['Virtual Mentoring']          = StringHelpers::glueUpSlugify( $contact->virtual_mentoring ) ?? '';
            $row['Years at current workplace'] = $contact->years_at_workplace ?? '';
            $row['LinkedIn Profile URL']       = $contact->linkedin ?? '';
            $row['Chapter Interest List']      = StringHelpers::glueUpSlugify( $contact->chapter_interest_list );
            $row['Chapter Leader Role']        = StringHelpers::glueUpSlugify( $contact->chapter_leader_role );
            $row['Event Attendance']           = $contact->compileEventInfo();
            $row['SilkStart Profile Notes']    = $contact->notes ?? '';
            $row['Bio']                        = $contact->bio ?? '';
            $row['Industry']                   = $contact->industry ?? '';

            $data[] = $row;
            $count ++;
            if ( $count % 1000 === 0 ) {
                $this->line( $timer->progress( $count, $total ) );
            }
        }
        $this->line( 'writing to a file' );
        $writer->writeSingleFileExcel( $data );
        $this->line( $timer->elapsed( 'Wrote to file' ) );

        return Command::SUCCESS;
    }
}
