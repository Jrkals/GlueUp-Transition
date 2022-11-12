<?php

namespace App\Console\Commands;

use App\Helpers\CSVWriter;
use App\Models\Plan;
use App\Models\YcpContact;
use Illuminate\Console\Command;

class ExportGlueUpMembers extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'glueup:exportMembers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports members to CSV by own member type';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $memberPlans = Plan::query()->get();
        foreach ( $memberPlans as $plan ) {
            $this->line( 'exporing csv for ' . $plan->name . '...' );
            $writer  = new CSVWriter( './storage/app/exports/members/' . $plan->name . '.csv' );
            $members = YcpContact::query()->whereRelation( 'plans', 'plan_id', '=', $plan->id )->get();
            $this->line( $plan->name . ' has ' . sizeof( $members ) . ' members' );
            $data = [];
            foreach ( $members as $member ) {
                $address = $member->billingAddress();
                $plan    = $member->getPlan( $plan->id );
                $data[]  = [
                    'Membership Start Date'        => $plan->pivot->start_date,
                    'Membership End Date'          => $plan->pivot->expiry_date,
                    'First Name'                   => $member->first_name,
                    'Last Name'                    => $member->last_name,
                    'Email'                        => $member->email,
                    'Phone'                        => $member->primaryPhone()?->number,
                    'Company Name'                 => $member->companyName(),
                    'Title/Position'               => '', //TODO in SS?
                    'Billing Address'              => isset( $address ) ? $address->street1 : '',
                    'Billing Country/Region'       => isset( $address ) ? $address->country : '',
                    'Billing Province/State'       => isset( $address ) ? $address->state : '',
                    'Billing Postal Code/Zip Code' => isset( $address ) ? $address->postal_code : '',
                    'Billing City'                 => isset( $address ) ? $address->city : '',
                    'Billing Company'              => '',
                    'Chapters'                     => $member->chapterIds(),
                    'Primary Chapter'              => $member->homeChapter()->glueUpId(),

                ];
            }

            $writer->writeData( $data, [], 'w' );
        }
        $this->line( 'done' );

        return Command::SUCCESS;
    }
}
