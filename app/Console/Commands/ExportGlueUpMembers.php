<?php

namespace App\Console\Commands;

use App\Helpers\ExcelWriter;
use App\Helpers\Timer;
use App\Models\Plan;
use App\Models\YcpContact;
use Illuminate\Console\Command;

class ExportGlueUpMembers extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'glueup:exportMembers {membershipType?}';

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
        $timer = new Timer();
        $timer->start();
        $membershipFilter = $this->argument( 'membershipType' );
        if ( $membershipFilter ) {
            $memberPlans = Plan::query()->where( 'name', '=', $membershipFilter )->get();
        } else {
            $memberPlans = Plan::query()->get();
        }
        $this->line( $timer->elapsed( 'fetched members' ) );

        foreach ( $memberPlans as $plan ) {
            $this->line( 'exporing csv for ' . $plan->name . '...' );
            $writer  = new ExcelWriter( './storage/app/exports/members/' . $plan->name . '.xlsx' );
            $members = YcpContact::query()->whereRelation( 'plans', 'plan_id', '=', $plan->id )
                                 ->whereDate( 'date_joined', '>=', '2023-01-01' )
                                 ->get();
            $this->line( $plan->name . ' has ' . sizeof( $members ) . ' members' );
            $data  = [];
            $count = 0;
            foreach ( $members as $member ) {
                $address = $member->billingAddress();
                $plan    = $member->getPlan( $plan->id );
                if ( ! $member->email || ( $plan->name === 'Legacy' && $member->subscribed === 'Unsubscribed' ) ) {
                    continue;
                }
                //For active chapter leaders, export their ycp email even if it is not with their membership in SS.
                $email = $member->email;
                if ( $plan->name === 'Chapter Leader' && $plan->pivot->active &&
                     ! str_contains( $member->email, 'ycp' ) ) {
                    $chapter = strtolower( str_replace( [ ' ', '-', 'YCP' ], '', $member->homeChapter()->name ) );
                    $email   = strtolower( str_replace( [ ' ' ], '', $member->first_name . '.' . $member->last_name . '@ycp' . $chapter . '.org' ) );
                    $this->line( 'Replacing' . $member->email . ' with ' . $email );
                }

                $data[] = [
                    'Membership Start Date'        => $plan->pivot->start_date,
                    'Membership End Date'          => $plan->pivot->expiry_date,
                    'First Name'                   => $member->first_name,
                    'Last Name'                    => $member->last_name,
                    'Email'                        => $email,
                    'Phone'                        => $member->primaryPhone()?->number,
                    //  'Company Name'                 => $member->companyName(),
                    //   'Title/Position'               => $member->title,
                    'Billing Address'              => isset( $address ) ? $address->street1 : '',
                    'Billing Country/Region'       => isset( $address ) ? $address->country : '',
                    'Billing Province/State'       => isset( $address ) ? $address->state : '',
                    'Billing Postal Code/Zip Code' => isset( $address ) ? $address->postal_code : '',
                    'Billing City'                 => isset( $address ) ? $address->city : '',
                    'Billing Company'              => '',
                    'Chapters'                     => ( $plan->name === 'Chapter Leader' || $plan->name === 'Chapter Board Member' ) ? $member->homeChapter( true )->glueUpId() : $member->chapterIds(),
                    'Primary Chapter'              => $member->homeChapter()->glueUpId(),
                ];
                $count ++;

                if ( $count % 100 === 0 ) {
                    $this->line( $timer->progress( $count, sizeof( $members ) ) );
                }
            }
            $this->line( 'writing to file...' );
            $writer->writeSingleFileExcel( $data );
        }

        return Command::SUCCESS;
    }
}
