<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YcpCompany extends Model {
    use HasFactory;

    public function contacts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany {
        return $this->belongsToMany( YcpContact::class )->withPivot( [ 'billing', 'contact' ] );
    }

    public function address(): \Illuminate\Database\Eloquent\Relations\MorphOne {
        return $this->morphOne( \App\Models\Address::class, 'addressable' );
    }

    public static function getCompany( array $row ): ?YcpCompany {
        $company = YcpCompany::query()->where( 'name', '=', $row['name'] )->get()->first();
        if ( $company ) {
            return $company;
        }

        return null;
    }

    public function fromCSV( array $row ) {
        $this->name = $row['name'];
        if ( $this->name === 'N/A' ) {
            return;
        }
        $this->short_description = $row['short_description'];
        $this->date_joined       = Carbon::parse( $row['date_joined'] )->toDateString();
        $this->expiry_date       = $row['expiry_date'] === 'Lifetime' ? Carbon::now()->addYears( 99 )->toDateString()
            : Carbon::parse( $row['expiry_date'] )->toDateString();
        $this->plan              = $row['plan'];
        $this->status            = $row['status'];
        if ( $this->status !== 'Inactive' ) {
            $this->plan = 'Company Recruiter Membership';
        }

        $this->email               = $row['email'];
        $this->website             = $row['website'];
        $this->phone               = $row['company_phone'] ? Phone::format( $row['company_phone'] ) : '';
        $this->overview            = $row['overview'] ? $this->stripHtml( $row['overview'] ) : '';
        $this->fax                 = $row['fax'] ? Phone::format( $row['fax'] ) : '';
        $this->number_of_employees = $row['number_of_employees'];

        $this->save();
        $this->address_id = Address::fromCSV( $row, 'company', $this->id )->id;
        $this->save();

        if ( ! empty( $row['billing_person'] ) ) {
            $billing_person = YcpContact::getOrCreateContact( $row['billing_person'], $row['billing_person_email'],
                $row['billing_person_title'] );
            $this->contacts()->save( $billing_person, [ 'billing' => true, 'contact' => false ] );

            //If you just assigned a billing person don't do contact person too
            return;
        }
        if ( ! empty( $row['contact_person'] ) ) {
            $contact_person = YcpContact::getOrCreateContact( $row['contact_person'], $row['contact_person_email'] );
            $this->contacts()->save( $contact_person, [ 'billing' => false, 'contact' => true ] );
        }
    }

    public static function companyMatches( array $company1, YcpCompany $company2 ): array {
        $differences = [
            'any' => false,
        ];

        //TODO fill this out. Maybe? maybe just wipe and do fresh imports

        return $differences;
    }

    public static function updateCompany( array $company1, YcpCompany $company2, array $differences ): YcpCompany {
        if ( ! $differences['any'] ) {
            return $company2;
        }

        $company2->save();

        return $company2;
    }

    public function getContactPerson(): ?YcpContact {
        if ( empty( $this->contacts ) ) {
            return null;
        }
        $contacts = $this->contacts;

        foreach ( $contacts as $contact ) {
            if ( $contact->pivot->contact ) {
                return $contact;
            }
        }

        return $contacts->first();

    }

    private function stripHtml( string $overview ) {
        $html = new \DOMDocument();
        try {
            $html->loadHTML( $overview );
        } catch ( \Exception $exception ) {
            return $overview;
        }

        return $html->textContent;
    }
}
