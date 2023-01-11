<?php

namespace App\Console\Commands;

use App\Helpers\ExcelWriter;
use App\Helpers\StringHelpers;
use App\Helpers\Timer;
use App\Models\Chapter;
use App\Models\YcpContact;
use Illuminate\Console\Command;

class ExportGlueUpContacts extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'glueup:exportContacts {Chapter?}';

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
        $chapterFilter = $this->argument( 'Chapter' );
        if ( $chapterFilter ) {
            $this->line( 'Exporting only chapter ' . $chapterFilter );
        }
        $timer = new Timer();
        $timer->start();
        $this->line( 'running query...' );
        $chapters = Chapter::query()->with( [
            'contacts',
            'contacts.events',
            'contacts.companies',
            'contacts.phones',
            'contacts.plans',
        ] )->get();
        $this->line( $timer->elapsed( 'Contacts Fetched' ) );
        foreach ( $chapters as $chapter ) {
            if ( $chapterFilter ) {
                if ( $chapter->name !== $chapterFilter ) {
                    continue;
                }
            }
            $data     = [];
            $contacts = $chapter->contacts;
            $count    = 0;

            $total = sizeof( $contacts );
            if ( $contacts->isEmpty() ) {
                $this->line( 'No Contacts for ' . $chapter->name );
                continue;
            }
            $this->line( 'exporting ' . sizeof( $contacts ) . ' contacts for ' . $chapter->name );
            $writer = new ExcelWriter( './storage/app/exports/contacts/' . $chapter->name . '.xlsx' );
            foreach ( $contacts as $contact ) {
                if ( ! $contact->email || $contact->subscribed === 'Unsubscribed' ) {
                    continue;
                }
                $address = $contact->address;
                $phone   = $contact->primaryPhone();

                $companyName                       = $contact->companyName();
                $row['First Name']                 = $this->cleanupName( $contact->first_name );// ? preg_replace( '/[[:^print:]]/', '', $contact->first_name ) : '';
                $row['Last Name']                  = $this->cleanupName( $contact->last_name );// ? preg_replace( '/[[:^print:]]/', '', $contact->last_name ) : '';
                $row['Address']                    = $address->street1 ?? '';
                $row['City']                       = $address->city ?? '';
                $row['State']                      = $address->state ?? '';
                $row['Postal Code']                = $address->postal_code ?? '';
                $row['Company']                    = $companyName;
                $row['Phone']                      = $phone?->number;
                $row['Email']                      = $contact->email;
                $row['Job Title']                  = $contact->title ?? '';
                $row['Date of Birth']              = $contact->birthday ?? '';
                $row['Spiritual Assessment']       = StringHelpers::glueUpSlugify( $contact->spiritual_assessment ) ?? '';
                $row['Professional Assessment']    = StringHelpers::glueUpSlugify( $contact->professional_assessment ) ?? '';
                $row['T Shirt Size']               = StringHelpers::glueUpSlugify( $contact->t_shirt_size ) ?? '';
                $row['Virtual Mentoring']          = StringHelpers::glueUpSlugify( $contact->virtual_mentoring ) ?? '';
                $row['Years at current workplace'] = $contact->years_at_workplace ?? '';
                $row['LinkedIn Profile URL']       = $contact->linkedin ?? '';
                $row['Chapter Leader Role']        = StringHelpers::mapChapterLeaderRole( $contact->chapter_leader_role );
                $row['Event Attendance']           = $contact->compileEventInfo();
                $row['Bio']                        = $contact->bio ?? '';
                $row['Professional Industry']      = $contact->industry ?? '';

                $data[] = $row;
                $count ++;
                if ( $count % 1000 === 0 ) {
                    $this->line( $timer->progress( $count, $total ) );
                }
            }
            $this->line( 'Writing Data for ' . $chapter->name );
            $writer->writeSingleFileExcel( $data );
        }

        return Command::SUCCESS;
    }

    private function cleanupName( $name ): string {
        if ( ! isset( $name ) ) {
            return 'Unknown';
        }
        if ( str_contains( $name, '@' ) ) {
            return 'Unknown';
        }
        $array = [ ')', '(', '^', '@', ',', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ];
        $a     = preg_replace( '/[[:^print:]]/', '', $name );
        $a     = str_replace( $array, '', $a );
        $a     = str_replace( '&', 'and', $a );

        return $a;
    }
}
