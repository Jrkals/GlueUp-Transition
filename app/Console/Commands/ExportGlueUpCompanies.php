<?php

namespace App\Console\Commands;

use App\Helpers\CSVWriter;
use App\Models\YcpCompany;
use Illuminate\Console\Command;

class ExportGlueUpCompanies extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'glueup:exportCompanies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports Companies to CSV';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $companies      = YcpCompany::query()->get();
        $companyWriter  = new CSVWriter( './storage/app/exports/companyExport.csv' );
        $companyColumns = [
            'Membership ID',
            'Membership Start Date',
            'Membership End Date',
            'Administrative Contact First Name',
            'Administrative Contact Last Name',
            'Administrative Contact Email',
            'Administrative Contact Phone',
            'Administrative Contact Company',
            'Administrative Contact Position',
            'Company Name',
            'Billing Address',
            'Billing Country/Region',
            'Billing Province/State',
            'Billing Post Code/Zip Code',
            'Billing City',
            'Billing Company',
            'Chapters',
            'Primary Chapter',
        ];
        $output         = [];

        foreach ( $companies as $company ) {
            $companyAddress = $company->address;
            $contactPerson  = $company->getContactPerson();
            $output[]       = [
                'Membership ID'                     => config( 'membership.company.' . $company->plan ),
                'Membership Start Date'             => $company->date_joined,
                'Membership End Date'               => $company->expiry_date,
                'Administrative Contact First Name' => isset( $contactPerson ) ? $contactPerson->first_name : '',
                'Administrative Contact Last Name'  => isset( $contactPerson ) ? $contactPerson->last_name : '',
                'Administrative Contact Email'      => isset( $contactPerson ) ? $contactPerson->email : '',
                'Administrative Contact Phone'      => isset( $contactPerson ) ? $contactPerson->workPhone()?->number : '',
                'Administrative Contact Company'    => $company->name,
                'Administrative Contact Position'   => '', //TODO is this in SS?
                'Company Name'                      => $company->name,
                'Billing Address'                   => $companyAddress->street1,
                'Billing Country/Region'            => $companyAddress->country,
                'Billing Province/State'            => $companyAddress->state,
                'Billing Post Code/Zip Code'        => $companyAddress->postal_code,
                'Billing City'                      => $companyAddress->city,
                'Billing Company'                   => $company->name,
                'Chapters'                          => '',
                'Primary Chapter'                   => 'YCP', //TODO make sure the national chapter name is right
            ];
        }

        $companyWriter->writeData( $output, $companyColumns, 'w' );

        return Command::SUCCESS;
    }
}
