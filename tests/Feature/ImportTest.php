<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\EmailValidation;
use App\Models\YcpCompany;
use App\Models\YcpContact;
use App\Models\YcpEvent;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;


class ImportTest extends TestCase {
    use refreshDatabase;

    protected $seed = true;
    private $testContactDir = 'testContacts/';
    private $testAddressDir = 'testAddresses/';
    private $testEventDir = 'testEvents/';
    private $testCompanyDir = 'testCompanies/';

    public function setUp(): void {
        parent::setUp();
        $this->seed( DatabaseSeeder::class );
        Storage::disk( 'local' )->makeDirectory( $this->testContactDir );
        Storage::disk( 'local' )->makeDirectory( $this->testAddressDir );
        Storage::disk( 'local' )->makeDirectory( $this->testEventDir );
        Storage::disk( 'local' )->makeDirectory( $this->testCompanyDir );

        $this->setupContactFiles();
        $this->setupAddressFiles();
        $this->setupEventFiles();
        $this->setupCompanyFiles();
    }

    public function tearDown(): void {
        parent::tearDown();
        Storage::disk( 'local' )->deleteDirectory( $this->testContactDir );
        Storage::disk( 'local' )->deleteDirectory( $this->testAddressDir );
        Storage::disk( 'local' )->deleteDirectory( $this->testEventDir );
        Storage::disk( 'local' )->deleteDirectory( $this->testCompanyDir );
    }

    private function setupContactFiles() {
        $contacts            = [];
        $contact1            = [
            'Name'                                                     => 'Justin Kalan',
            'T-shirt size'                                             => 'L',
            'Bio'                                                      => 'Development Director',
            'Notes'                                                    => 'Unsubscribed in NationBuilder',
            'Would you be open to participating in virtual mentoring?' => "[u'', u'1']",
            'Spiritual Assessment'                                     => '2 - Catholic, but not attending Mass regularly',
            'Professional Assessment'                                  => '4 - Right profession, integrating faith into the workplace, but could use additional resources',
            'Years at current workplace'                               => '0-3 years',
            'Chapter Interest List'                                    => 'boston',
            'Current chapter role category'                            => 'Operations',
            'Potential YCP City'                                       => 'Boston, MA',
            'First Name'                                               => 'Justin',
            'Last Name'                                                => 'Kalan',
            'Subscribed'                                               => 'Not Subscribed',
            'Email'                                                    => 'jkalan@wordonfire.org',
            'Mobile Phone'                                             => '5127334239',
            'Home Phone'                                               => '7708837611',
            'Business Phone'                                           => '404-242-7712',
            'Linkedin Profile'                                         => 'https://www.linkedin.com/in/beatrice-torralba-shakal-0152734a/',
            'Companies'                                                => "Saint Paul's Outreach",
            'NationBuilder Tags'                                       => 'attendee-ess-2020-1, attendee-ess-baugh-1-20, shared, attendee-ess-Joseph-Galati, attendee-ess-2018-05',
            'Date of Birth'                                            => '27 Mar 1985',
            'Last Renewal Date'                                        => '28 Nov 2022',
            'Last Renewed Plan'                                        => 'Belong Plus',
            'Latest Plan'                                              => 'Belong Plus',
            'Chapter Admin?'                                           => 'TRUE',
            'Status'                                                   => 'Active',
            'Plan'                                                     => 'Belong Plus',
            'Expiry Date'                                              => '01 Oct 2023',
            'Expiry Type'                                              => 'Manual Renewal',
            'Date Joined'                                              => '22 Aug 2020',
            'Last Login'                                               => '13 Oct 2022',
            'Home Chapter'                                             => 'YCP - Austin',
            'Active Chapters'                                          => 'YCP - Austin',
            'Other Chapters'                                           => 'YCP - Houston, YCP - Austin',
        ];
        $invalidEmail        = [
            'Name'                                                     => 'Constance de Monts',
            'T-shirt size'                                             => '',
            'Bio'                                                      => '',
            'Notes'                                                    => '',
            'Would you be open to participating in virtual mentoring?' => "TRUE",
            'Spiritual Assessment'                                     => '',
            'Professional Assessment'                                  => '',
            'Years at current workplace'                               => '',
            'Chapter Interest List'                                    => '',
            'Current chapter role category'                            => '',
            'Potential YCP City'                                       => '',
            'First Name'                                               => 'Constance',
            'Last Name'                                                => 'de Monts',
            'Subscribed'                                               => 'Not Subscribed',
            'Email'                                                    => EmailValidation::query()->where( 'valid', '=', false )->first()->email,
            'Mobile Phone'                                             => '',
            'Home Phone'                                               => '',
            'Business Phone'                                           => '',
            'Linkedin Profile'                                         => 'invalid url',
            'Companies'                                                => "",
            'NationBuilder Tags'                                       => 'shared, 2/6/16-import',
            'Date of Birth'                                            => '',
            'Last Renewal Date'                                        => '',
            'Last Renewed Plan'                                        => '',
            'Latest Plan'                                              => 'Annual Membership',
            'Chapter Admin?'                                           => '',
            'Status'                                                   => 'Expired',
            'Plan'                                                     => '',
            'Expiry Date'                                              => '09 Jan 2022',
            'Expiry Type'                                              => 'Manual Renewal',
            'Date Joined'                                              => '09 Jan 2021',
            'Last Login'                                               => '',
            'Home Chapter'                                             => 'YCP - Austin',
            'Active Chapters'                                          => '',
            'Other Chapters'                                           => 'YCP - Houston, YCP - Austin',
        ];
        $contact3            = [
            'Name'                                                     => 'Constance de Monts',
            'T-shirt size'                                             => '',
            'Bio'                                                      => 'Information Technology',
            'Notes'                                                    => '',
            'Would you be open to participating in virtual mentoring?' => "TRUE",
            'Spiritual Assessment'                                     => '',
            'Professional Assessment'                                  => '',
            'Years at current workplace'                               => '',
            'Chapter Interest List'                                    => '',
            'Current chapter role category'                            => '',
            'Potential YCP City'                                       => '',
            'First Name'                                               => 'Constance',
            'Last Name'                                                => 'de Monts',
            'Subscribed'                                               => 'Not Subscribed',
            'Email'                                                    => EmailValidation::query()->where( 'valid', '=', true )->first()->email,
            'Mobile Phone'                                             => '',
            'Home Phone'                                               => '',
            'Business Phone'                                           => '',
            'Linkedin Profile'                                         => 'invalid url',
            'Companies'                                                => "",
            'NationBuilder Tags'                                       => 'shared, 2/6/16-import, attendee-ess-2020-1',
            'Date of Birth'                                            => '',
            'Last Renewal Date'                                        => '',
            'Last Renewed Plan'                                        => '',
            'Latest Plan'                                              => 'Annual Membership',
            'Chapter Admin?'                                           => '',
            'Status'                                                   => 'Expired',
            'Plan'                                                     => '',
            'Expiry Date'                                              => '09 Jan 2022',
            'Expiry Type'                                              => 'Manual Renewal',
            'Date Joined'                                              => '09 Jan 2021',
            'Last Login'                                               => '',
            'Home Chapter'                                             => 'YCP - Austin',
            'Active Chapters'                                          => '',
            'Other Chapters'                                           => 'YCP - Houston, YCP - Austin',
        ];
        $contacts[]          = $contact1;
        $contacts[]          = $invalidEmail;
        $contacts[]          = $contact3;
        $contactsFileContent = $this->turnArraysToFileContent( $contacts );
        Storage::disk( 'local' )->put( $this->testContactDir . '/contacts.csv',
            $contactsFileContent );
    }

    private function setupAddressFiles() {
        $addressJustin              = [
            'Name'                => 'Justin Kalan',
            'First Name'          => 'Justin',
            'Last Name'           => 'Kalan',
            'Business Phone'      => '',
            'Mobile Phone'        => '',
            'Notes'               => 'Unsubscribed in NationBuilder',
            'Last Renewal Date'   => '28 Nov 2022',
            'Last Renewed Plan'   => 'Belong Plus',
            'NationBuilder Tags'  => 'attendee-ess-2020-1, attendee-ess-baugh-1-20, shared, attendee-ess-Joseph-Galati, attendee-ess-2018-05',
            'Home Phone'          => '7708837611',
            'Date Joined'         => '27 Mar 1985',
            'Expiry Date'         => 'Manual Renewal',
            'Plan'                => 'Belong Plus',
            'Status'              => 'Active',
            'Expiry Type'         => 'Manual Renewal',
            'Email'               => 'jkalan@wordonfire.org',
            'Work City'           => 'Irving',
            'Work Country'        => 'US',
            'Work Postal'         => '75123',
            'Work Province'       => 'TX',
            'Home Street Address' => '2041 Texas Plaza Dr',
            'Work Address 2'      => 'Ste 360',
            'Home Address 2'      => 'Apt 4210',
            'Home City'           => 'Irving',
            'Home Postal'         => '75062',
            'Home Province'       => 'TX',
            'Home Country'        => 'US',
            'Work Street Address' => '8445 Freeport Parkway',
            'Home Chapter'        => 'YCP - Austin',
            'Active Chapters'     => 'YCP - Austin',
            'Other Chapters'      => 'YCP - Austin, YCP - Houston',
        ];
        $newPersonAddress           = [
            'Name'                => 'New Contact',
            'First Name'          => 'New',
            'Last Name'           => 'Contact',
            'Business Phone'      => '',
            'Mobile Phone'        => '15052595600',
            'Notes'               => 'Unsubscribed in NationBuilder',
            'Last Renewal Date'   => '',
            'Last Renewed Plan'   => '',
            'NationBuilder Tags'  => '',
            'Home Phone'          => '',
            'Date Joined'         => '',
            'Expiry Date'         => '',
            'Plan'                => '',
            'Status'              => 'Contact',
            'Expiry Type'         => '',
            'Email'               => 'new@wordonfire.org',
            'Work City'           => 'Irving',
            'Work Country'        => 'US',
            'Work Postal'         => '75123',
            'Work Province'       => 'TX',
            'Home Street Address' => '11020 Huebner Oaks Apt 2318',
            'Work Address 2'      => 'Ste 360',
            'Home Address 2'      => '',
            'Home City'           => 'San Antonio',
            'Home Postal'         => '75062',
            'Home Province'       => 'TX',
            'Home Country'        => 'US',
            'Work Street Address' => '8446 Freeport Parkway',
            'Home Chapter'        => 'YCP - Chicago',
            'Active Chapters'     => '',
            'Other Chapters'      => 'YCP - St Louis',
        ];
        $addresses                  = [ $addressJustin, $newPersonAddress ];
        $addressContactsFileContent = $this->turnArraysToFileContent( $addresses );
        Storage::disk( 'local' )->put( $this->testAddressDir . '/addresses.csv',
            $addressContactsFileContent );
    }

    private function setupEventFiles() {
        $nhh              = [
            'Event'         => 'Networking Happy Hour',
            'Event Date'    => '20 Oct 2022',
            'Attendee Name' => 'Justin Kalan',
            'Attended'      => '',
            'Email'         => 'jkalan@wordonfire.org',
        ];
        $nhh_new_person   = [
            'Event'         => 'Networking Happy Hour',
            'Event Date'    => '20 Oct 2022',
            'Attendee Name' => 'New Smith',
            'Attended'      => '',
            'Email'         => 'csmith@test.org',
        ];
        $nhh_constance    = [
            'Event'         => 'Networking Happy Hour',
            'Event Date'    => '20 Oct 2022',
            'Attendee Name' => 'Constance de Monts',
            'Attended'      => '',
            'Email'         => EmailValidation::query()->where( 'valid', '=', true )->first()->email,
        ];
        $sjs              = [
            'Event'         => 'Saint Joseph Saturday',
            'Event Date'    => '10 Dec 2022',
            'Attendee Name' => 'Justin Kalan',
            'Attended'      => 'TRUE',
            'Email'         => 'jkalan@wordonfire.org',
        ];
        $ess              = [
            'Event'         => "October 2022 YCP OC ESS Featuring Amy D'Ambra",
            'Event Date'    => '19 Oct 2022',
            'Attendee Name' => 'Justin Kalan',
            'Attended'      => '',
            'Email'         => 'jkalan@wordonfire.org',
        ];
        $panel            = [
            'Event'         => 'Virtue Panel Discussion: Charity',
            'Event Date'    => '11 Oct 2022',
            'Attendee Name' => 'Justin Kalan',
            'Attended'      => 'TRUE',
            'Email'         => 'jkalan@wordonfire.org',
        ];
        $panel_diff_date  = [
            'Event'         => 'Virtue Panel Discussion: Charity',
            'Event Date'    => '12 Oct 2022',
            'Attendee Name' => 'Justin Kalan',
            'Attended'      => '',
            'Email'         => 'jkalan@wordonfire.org',
        ];
        $other            = [
            'Event'         => 'YCP Boston Turns One! ',
            'Event Date'    => '29 Sep 2022',
            'Attendee Name' => 'Justin Kalan',
            'Attended'      => '',
            'Email'         => 'jkalan@wordonfire.org',
        ];
        $events           = [
            $nhh,
            $ess,
            $other,
            $panel,
            $panel_diff_date,
            $sjs,
            $nhh_new_person,
            $nhh_constance
        ];
        $eventFileContent = $this->turnArraysToFileContent( $events );
        Storage::disk( 'local' )->put( $this->testEventDir . '/events.csv',
            $eventFileContent );
    }

    private function setupCompanyFiles() {
        $inactive            = [
            'Name'                 => 'Diocese of Dallas',
            'Company Phone'        => '414-483-6712',
            'Short Description'    => 'By tapping into the physical & spiritual nature of water, we empower people in developing countries to generate clean water solutions!',
            'Overview'             => '<p data-mce-style="font-family: helvetica; font-size: 300%; color: #0e3659; line-height: 1.2; font-weight: normal; text-align: left;">FINANCIAL LEADERSHIP</p>  <p data-mce-style="font-size: 110%;">Success is seldom a solo act. Nowhere is this more true than in personal, family, and enterprise financial strategies. Given the complex, high-stakes nature of what we do, we have assembled a team of more than 150 advisors and specialists, over 2,000 active brokers, dozens of staff, and an international network of resources and support.</p>  <p data-mce-style="font-family: helvetica; font-size: 300%; color: #0e3659; line-height: 1.2; font-weight: normal; text-align: right; padding-top: 30px;">LEADING THE WAY FORWARD</p>  <p data-mce-style="font-size: 110%; text-align: right;">You&rsquo;ll be working with a diverse team with uniquely competitive capabilities. Pacific Advisors has earned EDGE certification for gender equality in professional development, mentoring, recruitment, promotion efforts, and community culture. Our commitment to equal value, respect, and access to opportunities for all members of our team lets you succeed in your objectives.</p>  <p><span class="richtext"></span></p>',
            'Number of Members'    => '',
            'Date Joined'          => '',
            'Number of Employees'  => '48,700',
            'Fax'                  => '555-555-5555',
            'Expiry Date'          => '',
            'Plan'                 => '',
            'Status'               => 'Inactive',
            'Email'                => 'jorge.perez@cbrealty.com',
            'Billing Person'       => 'Jorge Perez',
            'Billing Person Email' => 'jorgeoforlando@gmail.com',
            'Billing Person Title' => 'REALTOR',
            'Contact Person'       => 'Jorge Perez',
            'Contact Person Email' => 'jorgeoforlando@gmail.com',
            'Website'              => 'https://www.coldwellbanker.com/coldwell-banker-residential-real-estate_-florida-12629c/winter-park-office-363347d',
            'City'                 => 'Winter Park',
            'Province'             => 'TX',
            'Street Address'       => '400 S Park Ave Ste 210',
            'Street Address 2'     => '',
            'Postal/Zip Code'      => '30080',
            'Country'              => 'US',
            'Date Added'           => '20 Aug 2020',
        ];
        $active              = [
            'Name'                 => 'Advocates for Community Transformation',
            'Company Phone'        => '13213275360',
            'Short Description'    => '',
            'Overview'             => '',
            'Number of Members'    => '',
            'Date Joined'          => '26 Oct 2022',
            'Number of Employees'  => '',
            'Fax'                  => '',
            'Expiry Date'          => '26 Oct 2023',
            'Plan'                 => 'Company Recruiter Membership',
            'Status'               => 'Active',
            'Email'                => 'companyEmail@wordonfire.org',
            'Billing Person'       => 'Justin Kalan',
            'Billing Person Email' => 'jkalan@wordonfire.org',
            'Billing Person Title' => 'Billing Person Title',
            'Contact Person'       => 'Samantha Smith',
            'Contact Person Email' => 'smcdonald@catholiccastmedia.com',
            'Website'              => '',
            'City'                 => '',
            'Province'             => '',
            'Street Address'       => '',
            'Street Address 2'     => '',
            'Postal/Zip Code'      => '',
            'Country'              => '',
            'Date Added'           => '20 Aug 2020',
        ];
        $companies           = [ $inactive, $active ];
        $companyFileContents = $this->turnArraysToFileContent( $companies );
        Storage::disk( 'local' )->put( $this->testCompanyDir . '/companies.csv',
            $companyFileContents );
    }

    private function turnArraysToFileContent( array $data ): string {
        $count = 0;
        $rv    = '';
        foreach ( $data as $datum ) {
            if ( $count === 0 ) {
                $rv .= implode( ',', array_keys( $datum ) );
                $rv .= "\n";
            }
            // Courtesy of https://www.php.net/manual/en/function.fputcsv.php notes
            // output up to 5MB is kept in memory, if it becomes bigger it will automatically be written to a temporary file
            $csv = fopen( 'php://temp/maxmemory:' . ( 5 * 1024 * 1024 ), 'r+' );
            fputcsv( $csv, $datum );
            rewind( $csv );
            // put it all in a variable
            $rv .= stream_get_contents( $csv );

            $count ++;
        }

        return $rv;
    }

    public function testImportContactsAlone() {
        $this->assertDatabaseCount( 'ycp_contacts', 0 );
        $this->artisan( 'silkstart:importContacts', [ 'file' => './storage/app/' . $this->testContactDir ] );
        $this->assertDatabaseCount( 'chapters', 2 );
        $this->assertDatabaseCount( 'phones', 3 );
        $this->assertDatabaseCount( 'plan_ycp_contact', 2 );
        $this->assertDatabaseCount( 'plans', 2 );
        $this->assertDatabaseCount( 'chapter_ycp_contact', 4 );
        $this->assertDatabaseCount( 'ycp_contacts', 2 );
        $this->assertDatabaseCount( 'ycp_events', 4 );
        $this->assertDatabaseCount( 'ycp_events_contacts', 5 );


        $this->assertDatabaseHas( 'ycp_contacts', [
            'first_name'              => 'Justin',
            'last_name'               => 'Kalan',
            'full_name'               => 'Justin Kalan',
            'email'                   => 'jkalan@wordonfire.org',
            'nb_tags'                 => 'attendee-ess-2020-1, attendee-ess-baugh-1-20, shared, attendee-ess-Joseph-Galati, attendee-ess-2018-05',
            'admin'                   => true,
            'date_joined'             => '2020-08-22',
            'birthday'                => '1985-03-27',
            'title'                   => null,
            'subscribed'              => 'Unsubscribed',
            'spiritual_assessment'    => '2 - Catholic, but not attending Mass regularly',
            'professional_assessment' => '4 - Right profession, integrating faith into the workplace, but could use additional resources',
            't_shirt_size'            => 'L',
            'virtual_mentoring'       => 'Yes',
            'years_at_workplace'      => '0-3 years',
            'chapter_interest_list'   => 'boston',
            'linkedin'                => 'https://www.linkedin.com/in/beatrice-torralba-shakal-0152734a/',
            'chapter_leader_role'     => 'Operations',
            'notes'                   => '',
            'bio'                     => 'Development Director',
            'industry'                => null
        ] );

        $this->assertDatabaseHas( 'ycp_contacts', [
            'full_name'  => 'Constance de Monts',
            'first_name' => 'Constance',
            'last_name'  => 'de Monts',
            'linkedin'   => '',
            'industry'   => 'COMPSC'
        ] );
        $this->assertDatabaseHas( 'plan_ycp_contact', [
            'active'      => true,
            'start_date'  => '2022-11-28',
            'expiry_date' => '2023-10-01',
            'expiry_type' => 'Manual Renewal',
        ] );

        $this->assertDatabaseHas( 'plan_ycp_contact', [
            'active'      => false,
            'start_date'  => '2021-01-09',
            'expiry_date' => '2022-01-09',
            'expiry_type' => 'Manual Renewal',
        ] );
        $austin    = Chapter::query()->where( 'name', '=', 'YCP - Austin' )->first();
        $houston   = Chapter::query()->where( 'name', '=', 'YCP - Houston' )->first();
        $justin    = YcpContact::query()->where( 'email', '=', 'jkalan@wordonfire.org' )->first();
        $constance = YcpContact::query()->where( 'full_name', '=', 'Constance de Monts' )->first();

        $this->assertDatabaseHas( 'chapter_ycp_contact', [
            'home'           => true,
            'ycp_contact_id' => $justin->id,
            'chapter_id'     => $austin->id,
        ] );
        $this->assertDatabaseHas( 'chapter_ycp_contact', [
            'home'           => false,
            'ycp_contact_id' => $justin->id,
            'chapter_id'     => $houston->id,
        ] );
        $this->assertDatabaseHas( 'chapter_ycp_contact', [
            'home'           => true,
            'ycp_contact_id' => $constance->id,
            'chapter_id'     => $austin->id,
        ] );
        $this->assertDatabaseHas( 'chapter_ycp_contact', [
            'home'           => false,
            'ycp_contact_id' => $constance->id,
            'chapter_id'     => $houston->id,
        ] );

        $ess = YcpEvent::query()->where( 'name', '=', 'attendee-ess-2020-1' )->first();
        $this->assertDatabaseHas( 'ycp_events_contacts', [
            'ycp_contact_id' => $constance->id,
            'ycp_event_id'   => $ess->id,
        ] );
        $this->assertDatabaseHas( 'ycp_events_contacts', [
            'ycp_contact_id' => $justin->id,
            'ycp_event_id'   => $ess->id,
        ] );

        $this->assertDatabaseHas( 'phones', [
            'ycp_contact_id' => $justin->id,
            'type'           => 'home'
        ] );
        $this->assertDatabaseHas( 'phones', [
            'ycp_contact_id' => $justin->id,
            'type'           => 'mobile'
        ] );
        $this->assertDatabaseHas( 'phones', [
            'ycp_contact_id' => $justin->id,
            'type'           => 'business'
        ] );

    }

    public function testImportContactsAndCompanies() {
        $this->artisan( 'silkstart:importContacts', [ 'file' => './storage/app/' . $this->testContactDir ] );
        $this->artisan( 'silkstart:importCompanies', [ 'file' => './storage/app/' . $this->testCompanyDir ] );
        $this->assertDatabaseCount( 'chapters', 2 );
        $this->assertDatabaseCount( 'phones', 3 );
        $this->assertDatabaseCount( 'plan_ycp_contact', 2 );
        $this->assertDatabaseCount( 'plans', 2 );
        $this->assertDatabaseCount( 'chapter_ycp_contact', 4 );
        $this->assertDatabaseCount( 'ycp_contacts', 3 );
        $this->assertDatabaseCount( 'addresses', 1 );
        $this->assertDatabaseCount( 'ycp_company_ycp_contact', 2 );

        $this->assertDatabaseHas( 'ycp_companies', [
            'name'                => 'Diocese of Dallas',
            'short_description'   => 'By tapping into the physical & spiritual nature of water, we empower people in developing countries to generate clean water solutions!',
            'date_joined'         => null,
            'expiry_date'         => null,
            'plan'                => '',
            'status'              => 'Inactive',
            'email'               => 'jorge.perez@cbrealty.com',
            'website'             => 'https://www.coldwellbanker.com/coldwell-banker-residential-real-estate_-florida-12629c/winter-park-office-363347d',
            'phone'               => '1 (414) 483-6712',
            'overview'            => 'FINANCIAL LEADERSHIP  Success is seldom a solo act. Nowhere is this more true than in personal, family, and enterprise financial strategies. Given the complex, high-stakes nature of what we do, we have assembled a team of more than 150 advisors and specialists, over 2,000 active brokers, dozens of staff, and an international network of resources and support.  LEADING THE WAY FORWARD  You’ll be working with a diverse team with uniquely competitive capabilities. Pacific Advisors has earned EDGE certification for gender equality in professional development, mentoring, recruitment, promotion efforts, and community culture. Our commitment to equal value, respect, and access to opportunities for all members of our team lets you succeed in your objectives.  ',
            'fax'                 => '',
            'number_of_employees' => '48,700',
        ] );
        $this->assertDatabaseHas( 'ycp_companies', [
            'name'        => 'Advocates for Community Transformation',
            'date_joined' => '2022-10-26',
            'expiry_date' => '2023-10-26',
            'plan'        => 'Company Recruiter Membership',
            'status'      => 'Active',
            'email'       => 'companyEmail@wordonfire.org',
            'phone'       => '1 (321) 327-5360',
        ] );
        $justin    = YcpContact::query()->with( 'companies' )->where( 'email', '=', 'jkalan@wordonfire.org' )->first();
        $jorge     = YcpContact::query()->with( 'companies' )->where( 'full_name', '=', 'Jorge Perez' )->first();
        $cathDal   = YcpCompany::query()->with( 'contacts' )->where( 'name', '=', 'Diocese of Dallas' )->first();
        $advocates = YcpCompany::query()->with( 'contacts' )->where( 'name', '=', 'Advocates for Community Transformation' )->first();

        $this->assertEquals( 'Billing Person Title', $justin->title );
        $this->assertDatabaseHas( 'ycp_company_ycp_contact', [
            'ycp_contact_id' => $justin->id,
            'ycp_company_id' => $advocates->id,
            'billing'        => true,
            'contact'        => false,
        ] );
        $this->assertDatabaseMissing( 'ycp_company_ycp_contact', [
            'ycp_company_id' => $advocates->id,
            'billing'        => false,
            'contact'        => true,
        ] );
        $this->assertDatabaseHas( 'ycp_company_ycp_contact', [
            'ycp_contact_id' => $jorge->id,
            'ycp_company_id' => $cathDal->id,
            'billing'        => true,
            'contact'        => false,
        ] );

        $this->assertDatabaseHas( 'addresses', [
            'street1'          => '400 S Park Ave Ste 210',
            'street2'          => '',
            'city'             => 'Winter Park',
            'state'            => 'TX',
            'postal_code'      => '30080',
            'country'          => 'US',
            'addressable_id'   => $cathDal->id,
            'addressable_type' => YcpCompany::class,
        ] );
    }

    public function testImportContactsAndAddresses() {
        $this->artisan( 'silkstart:importContacts', [ 'file' => './storage/app/' . $this->testContactDir ] );
        $this->artisan( 'silkstart:importContacts', [ 'file' => './storage/app/' . $this->testAddressDir ] );
        $this->assertDatabaseCount( 'chapters', 4 );
        $this->assertDatabaseCount( 'phones', 4 );
        $this->assertDatabaseCount( 'chapter_ycp_contact', 6 );
        $this->assertDatabaseCount( 'ycp_contacts', 3 );
        $this->assertDatabaseCount( 'addresses', 4 );
        $justin     = YcpContact::query()->with( 'companies' )->where( 'email', '=', 'jkalan@wordonfire.org' )->first();
        $newContact = YcpContact::query()->with( 'companies' )->where( 'email', '=', 'new@wordonfire.org' )->first();

        $this->assertDatabaseHas( 'addresses', [
            'street1'          => '2041 Texas Plaza Dr',
            'street2'          => 'Apt 4210',
            'city'             => 'Irving',
            'state'            => 'TX',
            'postal_code'      => '75062',
            'country'          => 'US',
            'addressable_id'   => $justin->id,
            'addressable_type' => YcpContact::class,
            'address_type'     => 'home',
        ] );
        $this->assertDatabaseHas( 'addresses', [
            'street1'          => '8445 Freeport Parkway',
            'street2'          => 'Ste 360',
            'city'             => 'Irving',
            'state'            => 'TX',
            'postal_code'      => '75123',
            'country'          => 'US',
            'addressable_id'   => $justin->id,
            'addressable_type' => YcpContact::class,
            'address_type'     => 'business',

        ] );
        $this->assertDatabaseHas( 'addresses', [
            'street1'          => '11020 Huebner Oaks Apt 2318',
            'street2'          => '',
            'city'             => 'San Antonio',
            'state'            => 'TX',
            'postal_code'      => '75062',
            'country'          => 'US',
            'addressable_id'   => $newContact->id,
            'addressable_type' => YcpContact::class,
            'address_type'     => 'home',
        ] );
        $this->assertDatabaseHas( 'addresses', [
            'street1'          => '8446 Freeport Parkway',
            'street2'          => 'Ste 360',
            'city'             => 'Irving',
            'state'            => 'TX',
            'postal_code'      => '75123',
            'country'          => 'US',
            'addressable_id'   => $newContact->id,
            'addressable_type' => YcpContact::class,
            'address_type'     => 'business',
        ] );
    }

    public function testImportContactsAndEvents() {
        $this->artisan( 'silkstart:importContacts', [ 'file' => './storage/app/' . $this->testContactDir ] );
        $this->artisan( 'silkstart:importEvents', [ 'file' => './storage/app/' . $this->testEventDir ] );

        $justin    = YcpContact::query()->where( 'email', '=', 'jkalan@wordonfire.org' )->first();
        $newSmith  = YcpContact::query()->where( 'email', '=', 'csmith@test.org' )->first();
        $constance = YcpContact::query()->where( 'full_name', '=', 'Constance de Monts' )->first();

        $charity1 = YcpEvent::query()->where( [
            'date' => '2022-10-11',
            'name' => 'Virtue Panel Discussion: Charity'
        ] )->first();
        $charity2 = YcpEvent::query()->where( [
            'date' => '2022-10-12',
            'name' => 'Virtue Panel Discussion: Charity'
        ] )->first();

        $nhh = YcpEvent::query()->where( [
            'type' => 'NHH'
        ] )->first();

        $this->assertDatabaseCount( 'ycp_contacts', 3 );
        $this->assertDatabaseCount( 'ycp_events', 10 );
        $this->assertDatabaseCount( 'ycp_events_contacts', 13 );

        $this->assertDatabaseHas( 'ycp_events', [
            'date' => '2022-10-19',
            'type' => 'ESS',
            'name' => "October 2022 YCP OC ESS Featuring Amy D'Ambra",
        ] );
        $this->assertDatabaseHas( 'ycp_events', [
            'date' => '2022-10-20',
            'type' => 'NHH',
            'name' => "Networking Happy Hour",
        ] );
        $this->assertDatabaseHas( 'ycp_events', [
            'date' => '2022-12-10',
            'type' => 'SJS',
            'name' => "Saint Joseph Saturday",
        ] );
        $this->assertDatabaseHas( 'ycp_events', [
            'date' => '2022-10-11',
            'type' => 'Panel',
            'name' => "Virtue Panel Discussion: Charity",
        ] );
        $this->assertDatabaseHas( 'ycp_events', [
            'date' => '2022-10-12',
            'type' => 'Panel',
            'name' => "Virtue Panel Discussion: Charity",
        ] );
        $this->assertDatabaseHas( 'ycp_events', [
            'date' => '2022-09-29',
            'type' => 'Other',
            'name' => "YCP Boston Turns One! ",
        ] );

        $this->assertDatabaseHas( 'ycp_events_contacts', [
            'ycp_contact_id' => $justin->id,
            'ycp_event_id'   => $charity1->id
        ] );
        $this->assertDatabaseHas( 'ycp_events_contacts', [
            'ycp_contact_id' => $justin->id,
            'ycp_event_id'   => $charity2->id
        ] );
        $this->assertDatabaseHas( 'ycp_events_contacts', [
            'ycp_contact_id' => $newSmith->id,
            'ycp_event_id'   => $nhh->id
        ] );
        $this->assertDatabaseHas( 'ycp_events_contacts', [
            'ycp_contact_id' => $constance->id,
            'ycp_event_id'   => $nhh->id
        ] );
    }
}
