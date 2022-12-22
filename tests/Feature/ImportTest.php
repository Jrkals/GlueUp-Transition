<?php

namespace Tests\Feature;

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
//        $this->setupAddressFiles();
//        $this->setupEventFiles();
//        $this->setupCompanyFiles();
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
        $contacts[]          = $contact1;
        $contactsFileContent = $this->turnArraysToFileContent( $contacts );
        Storage::disk( 'local' )->put( $this->testContactDir . '/contacts.csv',
            $contactsFileContent );
    }

    private function turnArraysToFileContent( array $data ): string {
        $count = 0;
        $rv    = '';
        foreach ( $data as $datum ) {
            if ( $count === 0 ) {
                $rv .= implode( ',', array_keys( $datum ) );
                $rv .= "\n";
            }
            //Wrap rows with commas with quotations to mimic a CSV
            $rv .= implode( ',', array_map( function ( $element ) {
                if ( str_contains( $element, "," ) ) {
                    return '"' . $element . '"';
                }

                return $element;
            }, array_values( $datum ) ) );
            $rv .= "\n";

            $count ++;
        }

        return $rv;
    }

    public function testImportContacts() {
        $this->assertDatabaseCount( 'ycp_contacts', 0 );
        $this->artisan( 'silkstart:importContacts', [ 'file' => './storage/app/' . $this->testContactDir ] );
        $this->assertDatabaseCount( 'ycp_contacts', 1 );

    }
}