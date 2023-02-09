<?php

namespace App\Console\Commands;

use App\Helpers\CSVReader;
use App\Helpers\ExcelWriter;
use App\Helpers\Name;
use App\Helpers\StringHelpers;
use App\Models\Address;
use App\Models\Phone;
use App\Models\YcpContact;
use Illuminate\Console\Command;

class ImportConferenceAttendees extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:conference {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Takes a SS input file and returns a Glue Up file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $reader = new CSVReader( $this->argument( 'file' ) );

        $inputData   = $reader->extract_data();
        $ticketTypes = [];

        $preConferencePeople = []; //email => true/false if attending pre conf or not

        foreach ( $inputData as $row ) {
            $map                                  = StringHelpers::mapPreConf( $row['ticket_type'] );
            $preConferencePeople[ $row['email'] ] = $map;
        }

        foreach ( $inputData as $row ) {
            if ( ! str_contains( $row['session'], 'All Sessions' ) ) {
                continue; //Skip pre conference work tickets
            }

            //Only import missing people
            if ( ! empty( $row['vlookup'] ) ) {
                continue;
            }
            $contact = YcpContact::getContact( $row );
            $name    = Name::fromFullName( $row['attendee_name'] );
            $address = Address::fromFull( $row['address_keep_but_hide'] );
            $mapped  = [
                'First Name'                                   => $name->firstName(),
                'Last Name'                                    => $name->lastName(),
                'Email'                                        => $row['email'],
                'Phone'                                        => Phone::format( $row['phone_number'] ),
                'Address'                                      => $address->street1 ?? '',
                'Country/Region'                               => $address->country ?? '',
                'City'                                         => $address->city ?? '',
                'Province/State'                               => $address->state ?? '',
                'Postal Code/Zip Code'                         => $address->postal_code ?? '',
                'When will you be arriving to the Conference?' => StringHelpers::glueUpSlugify( $row['arrival'] ),
                'When will you be departing the Conference?'   => StringHelpers::glueUpSlugify( $row['departure'] ),
                'Pre-Conference Workshop'                      => $preConferencePeople[ $row['email'] ],
                'Saturday Evening Gala'                        => StringHelpers::glueUpSlugify( $row['saturday_gala'] ),
                'Saturday Lunch'                               => StringHelpers::glueUpSlugify( $row['saturday_lunch'] ),
                'Dietary Restrictions'                         => StringHelpers::mapDietaryRestrictions( $row['dietary_restrictions'] ),
                'Hotel'                                        => StringHelpers::glueUpSlugify( $row['hotel'] ),
                'T-Shirt Size'                                 => StringHelpers::glueUpSlugify( $row['t_shirt_size'] ),
                'Age'                                          => StringHelpers::glueUpSlugify( $row['age'] ),
                'Highest Professional Experience'              => StringHelpers::glueUpSlugify( $row['highest_professional_experience'] ),
                'Home Chapter'                                 => StringHelpers::glueUpSlugify( $row['home_ycp_chapter'] ),
                'Professional Industry'                        => ! empty( $row['industry'] ) ? ( new \App\Models\YcpContact )->mapIndustry( $row['industry'] ) : '',
                'National Leadership Summit'                   => StringHelpers::glueUpSlugify( $row['national_leadership_summit'] ),
                'Friday Lunch'                                 => StringHelpers::glueUpSlugify( $row['friday_lunch'] ),
                'Religious Order or Diocese Name'              => $row['religious_order_or_diocese_name'],
                'Religious Vocation'                           => StringHelpers::glueUpSlugify( $row['please_select_your_religious_vocation'] ),
                'Friday Afternoon Mass'                        => StringHelpers::glueUpSlugify( $row['for_priests_are_you_able_to_concelebrate_friday_afternoon_mass'] ),
                'Saturday Morning Mass'                        => StringHelpers::glueUpSlugify( $row['for_priests_are_you_able_to_concelebrate_saturday_morning_mass'] ),
                'Exposition'                                   => StringHelpers::glueUpSlugify( $row['for_priests_deacons_are_you_able_to_assist_with_exposition_of_the_blessed_sacrament_saturday_early_afternoon'] ),
                'Sunday Morning Mass'                          => StringHelpers::glueUpSlugify( $row['for_priests_are_you_able_to_concelebrate_sunday_morning_mass'] ),
                'Confession'                                   => StringHelpers::glueUpSlugify( $row['for_priests_are_you_available_to_assist_with_confession_throughout_the_weekend'] ),
                'Priest Stole'                                 => StringHelpers::glueUpSlugify( $row['for_priests_will_a_stole_need_to_be_provided_for_you'] ),
                'Comments'                                     => $row['for_priests_what_other_priestly_needs_or_requests_do_you_have_for_our_staff_to_address'],
                'Chapter Leader Role'                          => StringHelpers::mapChapterLeaderRole( $contact->chapter_leader_role ?? '' ),
                'Internal Note'                                => 'imported from Silkstart 1-18-23',
            ];
            if ( isset( $ticketTypes[ $row['ticket_type'] ] ) ) {
                $ticketTypes[ $row['ticket_type'] ][] = $mapped;
            } else {
                $ticketTypes[ $row['ticket_type'] ] = [ $mapped ];
            }
        }
        foreach ( $ticketTypes as $ticketType => $data ) {
            $ticketType = str_replace( [ '/', '(', ')', ' ' ], '', $ticketType );
            $writer     = new ExcelWriter( './storage/app/exports/events/2.0/' . $ticketType . '.xlsx' );
            $writer->writeSingleFileExcel( $data );
        }

        return Command::SUCCESS;
    }
}
