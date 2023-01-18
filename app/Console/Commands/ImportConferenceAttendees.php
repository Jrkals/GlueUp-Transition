<?php

namespace App\Console\Commands;

use App\Helpers\CSVReader;
use App\Helpers\ExcelWriter;
use App\Helpers\Name;
use App\Helpers\StringHelpers;
use App\Helpers\XLSXWriter;
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
        $writer = new ExcelWriter( './storage/app/exports/events/conference.csv' );

        $inputData  = $reader->extract_data();
        $outputData = [];

        foreach ( $inputData as $row ) {
//            $row          = [
//                'Session',
//                'Ticket Type',
//                'Attendee Name',
//                //TODO needs to be broken apart
//                'Email',
//                'Phone Number',
//                'Home YCP Chapter',
//                'Address',
//                //TODO needs to be broken apart
//                'Attending the Saturday VIP Lunch?',
//                'Age',
//                'Friday Lunch',
//                'Arrival',
//                'Departure',
//                'Hotel',
//                'Saturday Gala',
//                'Saturday Lunch',
//                'National Leadership Summit',
//                'Industry',
//                'For priests, what other priestly needs or requests do you have for our staff to address?',
//                'For priests, will a stole need to be provided for you?',
//                'For priests, are you available to assist with Confession throughout the weekend?',
//                'For priests, are you able to concelebrate Sunday morning Mass?',
//                'For priests, are you able to concelebrate Friday afternoon Mass?',
//                'For priests, are you able to concelebrate Saturday morning Mass?',
//                'For priests & deacons, are you able to assist with Exposition of the Blessed Sacrament (Saturday early afternoon)?',
//                'Highest Professional Experience',
//                'Please Select Your Religious Vocation',
//                'Religious Order or Diocese Name',
//                'Dietary Restrictions',
//                'If you have any dietary restrictions not listed above list them here',
//                'Attendee Status',
//                'Discount Codes',
//                'Membership Status',
//                'Membership Plan',
//                'Company Name',
//                'T-Shirt Size',
//                'Ticket Price',
//                'Amount Received',
//                'Purchase Date',
//                'Outstanding Balance',
//            ];
            $contact      = YcpContact::getContact( $row );
            $name         = Name::fromFullName( $row['attendee_name'] );
            $address      = Address::fromFull( $row['address'] );
            $outputData[] = [
                'First Name'                                       => $name->firstName(),
                'Last Name'                                        => $name->lastName(),
                'Email'                                            => $row['email'],
                'Phone'                                            => Phone::format( $row['phone_number'] ),
                //   'Company'                                          => $row['company_name'],
                //  'Title/Position',
                //  'Function',
                //  'Role',
                //  'Date of Birth',
                // 'Industry' => ( new \App\Models\YcpContact )->mapIndustry($row['industry']),
                'Address'                                          => $address->street1 ?? '',
                'Country/Region'                                   => $address->country ?? '',
                'City'                                             => $address->city ?? '',
                'Province/State'                                   => $address->state ?? '',
                'Postal Code/Zip Code'                             => $address->postal_code ?? '',
                'When will you be arriving to the Conference? - 1' => StringHelpers::glueUpSlugify( $row['arrival'] ),
                'When will you be departing the Conference? - 1'   => StringHelpers::glueUpSlugify( $row['departure'] ),
                'Pre-Conference Workshop'                          => '', //TODO figure out design your life
                'Saturday Evening Gala'                            => StringHelpers::glueUpSlugify( $row['saturday_gala'] ),
                'Saturday Lunch'                                   => StringHelpers::glueUpSlugify( $row['saturday_lunch'] ),
                'Dietary Restrictions'                             => StringHelpers::glueUpSlugify( $row['dietary_restrictions'] ),
                'Hotel'                                            => StringHelpers::glueUpSlugify( $row['hotel'] ),
                'T-Shirt Size'                                     => StringHelpers::glueUpSlugify( $row['t_shirt_size'] ),
                'Age'                                              => StringHelpers::glueUpSlugify( $row['age'] ),
                'Highest Professional Experience'                  => StringHelpers::glueUpSlugify( $row['highest_professional_experience'] ),
                'Home Chapter'                                     => $contact?->homeChapter()->glueUpId(),
                'Professional Industry'                            => ( new \App\Models\YcpContact )->mapIndustry( $row['industry'] ),
                'National Leadership Summit'                       => StringHelpers::glueUpSlugify( $row['national_leadership_summit'] ),
                'Friday Lunch'                                     => StringHelpers::glueUpSlugify( $row['friday_lunch'] ),
                'Religious Order or Diocese Name'                  => $row['religious_order_or_diocese_name'],
                'Religious Vocation'                               => $row['please_select_your_religious_vocation'],
                'Friday Afternoon Mass'                            => StringHelpers::glueUpSlugify( $row['for_priests_are_you_able_to_concelebrate_friday_afternoon_mass'] ),
                'Saturday Morning Mass'                            => StringHelpers::glueUpSlugify( $row['for_priests_are_you_able_to_concelebrate_saturday_morning_mass'] ),
                'Exposition'                                       => StringHelpers::glueUpSlugify( $row['for_priests_deacons_are_you_able_to_assist_with_exposition_of_the_blessed_sacrament_saturday_early_afternoon'] ),
                'Sunday Morning Mass'                              => StringHelpers::glueUpSlugify( $row['for_priests_are_you_able_to_concelebrate_sunday_morning_mass'] ),
                'Confession'                                       => StringHelpers::glueUpSlugify( $row['for_priests_are_you_available_to_assist_with_confession_throughout_the_weekend'] ),
                'Priest Stole'                                     => StringHelpers::glueUpSlugify( $row['for_priests_will_a_stole_need_to_be_provided_for_you'] ),
                'Comments'                                         => $row['for_priests_what_other_priestly_needs_or_requests_do_you_have_for_our_staff_to_address'],
                'Chapter Leader Role'                              => StringHelpers::mapChapterLeaderRole( $contact->chapter_leader_role ?? '' ),
                //   'Chapter Chaplain',
                //      'Guest',
                //     'Internal Note',
//                'Billing Address',
//                'Billing Country/Region',
//                'Billing Province/State',
//                'Billing Postal Code/Zip Code',
//                'Billing City'
            ];
        }

        $writer->writeSingleFileExcel( $outputData );

        return Command::SUCCESS;
    }
}
