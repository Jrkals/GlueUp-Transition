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
        $companies       = YcpCompany::query()->with( 'address' )->get();
        $memberCompanies = YcpCompany::query()->with( [ 'contacts', 'contacts.phones', 'address' ] )
                                     ->where( 'plan', '=', 'Company Recruiter Membership' )->get();

        $companyContactWriter = new CSVWriter( './storage/app/exports/companies/CompanyContactExport.xlsx' );
        $memberCompanyWriter  = new CSVWriter( './storage/app/exports/companies/memberCompanyExport.xlsx' );
        // $memberCompanyPeopleWriter = new CSVWriter( './storage/app/exports/companies/memberCompanyPeopleExport.csv' );

        $companyContactOutput      = [];
        $memberCompanyOutput       = [];
        $memberCompanyPeopleOutput = [];

        foreach ( $memberCompanies as $company ) {
            $companyAddress = $company->address;
            $contactPerson  = $company->getContactPerson();

            //This is for conference sponsors
            if ( ! isset( $contactPerson ) ) {
                continue;
            }
            $memberCompanyOutput[] = [
                'Membership ID'                     => $company->id,
                'Membership Start Date'             => $company->date_joined,
                'Membership End Date'               => $company->expiry_date,
                'Administrative Contact First Name' => $contactPerson->first_name,
                'Administrative Contact Last Name'  => $contactPerson->last_name,
                'Administrative Contact Email'      => $contactPerson->email,
                'Administrative Contact Phone'      => $contactPerson->workPhone()?->number,
                'Administrative Contact Company'    => $company->name,
                'Administrative Contact Position'   => $contactPerson->title,
                'Company Name'                      => $company->name,
                'Billing Address'                   => $companyAddress->street1,
                'Billing Country/Region'            => $companyAddress->country,
                'Billing Province/State'            => $companyAddress->state,
                'Billing Postal Code/Zip Code'      => $companyAddress->postal_code,
                'Billing City'                      => $companyAddress->city,
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

        foreach ( $companies as $company ) {
            $companyAddress         = $company->address;
            $companyContactOutput[] = [
                'Company Name'         => $company->name,
                'Address'              => $companyAddress->street1,
                'Country/Region'       => $companyAddress->country,
                'Province/State'       => $companyAddress->state,
                'Postal Code/Zip Code' => $companyAddress->postal_code,
                'City'                 => $companyAddress->city,
                'Phone'                => $company->phone,
                'Fax'                  => $company->fax,
                'Number of Employees'  => $company->number_of_employees,
                'Company Overview'     => $company->overview,
            ];
        }

        $companyContactWriter->writeSingleFileExcel( $companyContactOutput );
        $memberCompanyWriter->writeDualSheetExcelFile( $memberCompanyOutput, $memberCompanyPeopleOutput );

        return Command::SUCCESS;
    }
}
