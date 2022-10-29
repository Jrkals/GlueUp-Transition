<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YCPCompany extends Model {
    use HasFactory;

    public function contacts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany {
        return $this->belongsToMany( YCPContact::class )->withPivot( [ 'admin', 'contact' ] );
    }

    public function address() {
        return $this->morphOne( \App\Models\Address::class, 'addressable' );
    }

    public static function existsInDB( array $row ): bool {
        if ( YCPCompany::query()->where( 'name', '=', $row['name'] )->get()->isNotEmpty() ) {
            return true;
        }

        return false;
    }

    public function fromCSV( array $row ) {
        $this->name              = $row['name'];
        $this->short_description = $row['short_description'];
        $this->date_joined       = Carbon::parse( $row['date_joined'] )->toDateString();
        $this->expiry_date       = $row['expiry_date'] === 'Lifetime' ? Carbon::now()->addYears( 99 )->toDateString()
            : Carbon::parse( $row['expiry_date'] )->toDateString();
        $this->plan              = $row['plan'];
        $this->status            = $row['status'];
        $this->email             = $row['email'];
        $this->website           = $row['website'];

        $this->save();
        $this->address_id = Address::fromCSV( $row, 'company', $this->id )->id;
        $this->save();

        $billing_person = YCPContact::getOrCreateContact( $row['billing_person'], $row['billing_person_email'] );
        $contact_person = YCPContact::getOrCreateContact( $row['contact_person'], $row['contact_person_email'] );
        $this->contacts()->save( $billing_person, [ 'billing' => true, 'contact' => false ] );
        $this->contacts()->save( $contact_person, [ 'billing' => false, 'contact' => true ] );
    }
}
