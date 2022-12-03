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
        $inactiveCompanies = YcpCompany::query()->with( [ 'address' ] )
                                       ->where( 'plan', '!=', 'Company Recruiter Membership' )->get();
        $memberCompanies   = YcpCompany::query()->with( [ 'contacts', 'contacts.phones', 'address' ] )
                                       ->where( 'plan', '=', 'Company Recruiter Membership' )->get();

        $inactiveCompanyWriter     = new CSVWriter( './storage/app/exports/companies/inactiveCompanyExport.csv' );
        $memberCompanyWriter       = new CSVWriter( './storage/app/exports/companies/memberCompanyExport.csv' );
        $memberCompanyPeopleWriter = new CSVWriter( './storage/app/exports/companies/memberCompanyPeopleExport.csv' );

        $inactiveCompanyOutput     = [];
        $memberCompanyOutput       = [];
        $memberCompanyPeopleOutput = [];

        foreach ( $memberCompanies as $company ) {
            $companyAddress        = $company->address;
            $contactPerson         = $company->getContactPerson();
            $memberCompanyOutput[] = [
                'Membership ID'                     => $company->id,
                'Membership Start Date'             => $company->date_joined,
                'Membership End Date'               => $company->expiry_date,
                'Administrative Contact First Name' => isset( $contactPerson ) ? $contactPerson->first_name : '',
                'Administrative Contact Last Name'  => isset( $contactPerson ) ? $contactPerson->last_name : '',
                'Administrative Contact Email'      => isset( $contactPerson ) ? $contactPerson->email : '',
                'Administrative Contact Phone'      => isset( $contactPerson ) ? $contactPerson->workPhone()?->number : '',
                'Administrative Contact Company'    => $company->name,
                'Administrative Contact Position'   => isset( $contactPerson ) ? $contactPerson->title : '',
                'Company Name'                      => $company->name,
                'Billing Address'                   => $companyAddress->street1,
                'Billing Country/Region'            => $companyAddress->country,
                'Billing Province/State'            => $companyAddress->state,
                'Billing Post Code/Zip Code'        => $companyAddress->postal_code,
                'Billing City'                      => $companyAddress->city,
                'Billing Company'                   => $company->name,
                'Chapters'                          => '',
                'Primary Chapter'                   => '',
                'Phone'                             => $company->phone,
                'Fax'                               => $company->fax,
                'Number of Employees'               => $company->number_of_employees,
                'Company Overview'                  => $company->overview,
            ];

            foreach ( $company->contacts as $contact ) {
                $memberCompanyPeopleOutput[] = [
                    'Membership Id'  => $company->id,
                    'Primary Member' => (bool) $contact->pivot->billing,
                    'First Name'     => $contact->first_name,
                    'Last Name'      => $contact->last_name,
                    'Email'          => $contact->email,
                    'Phone'          => $contact->workPhone()?->number,
                    'Company Name'   => $company->name,
                ];
            }
        }

        foreach ( $inactiveCompanies as $company ) {
            $companyAddress          = $company->address;
            $contactPerson           = $company->getContactPerson();
            $inactiveCompanyOutput[] = [
                'Company Name'               => $company->name,
                'Billing Address'            => $companyAddress->street1,
                'Billing Country/Region'     => $companyAddress->country,
                'Billing Province/State'     => $companyAddress->state,
                'Billing Post Code/Zip Code' => $companyAddress->postal_code,
                'Billing City'               => $companyAddress->city,
                'Billing Company'            => $company->name,
                'Phone'                      => $company->phone,
                'Fax'                        => $company->fax,
                'Number of Employees'        => $company->number_of_employees,
                'Company Overview'           => $company->overview,
            ];
        }

        $inactiveCompanyWriter->writeData( $inactiveCompanyOutput, [], 'w' );
        $memberCompanyWriter->writeData( $memberCompanyOutput, [], 'w' );
        $memberCompanyPeopleWriter->writeData( $memberCompanyPeopleOutput, [], 'w' );

        return Command::SUCCESS;
    }
}
