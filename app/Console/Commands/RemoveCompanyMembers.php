<?php

namespace App\Console\Commands;

use App\Helpers\CSVReader;
use App\Models\Plan;
use App\Models\YcpContact;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RemoveCompanyMembers extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'silkstart:companyFix {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes company recruiters who were marked as legacy by a bug';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $reader        = new CSVReader( $this->argument( 'file' ) );
        $data          = $reader->extract_data();
        $legacyPlan    = Plan::query()->where( 'name', '=', 'Legacy' )->first();
        $recruiterPlan = Plan::query()->where( 'name', '=', 'Company Recruiter Membership' )->first();

        foreach ( $data as $row ) {
            $contact = YcpContact::query()->with( 'plans' )->where( 'email', '=', $row['email'] )->first();
            if ( ! $contact ) {
                $this->line( $row['email'] . ' not found' );
                continue;
            }

            $plans = $contact->plans;
            foreach ( $plans as $plan ) {
                if ( $plan->name === 'Legacy' ) {
                    $contact->plans()->detach( $legacyPlan->id );
                }
                if ( $plan->name === 'Company Recruiter Membership' ) {
                    goto end; //skip adding it
                }
            }

            $contact->plans()->save( $recruiterPlan, [
                'active'      => $row['status'] === 'Active',
                'expiry_date' => Carbon::parse( $row['expiry_date'] ),
                'expiry_type' => $row['expiry_type'] ?: 'Unknown',
                'start_date'  => Carbon::parse( $row['date_joined'] )
            ] );

            end:

        }

        return Command::SUCCESS;
    }
}
