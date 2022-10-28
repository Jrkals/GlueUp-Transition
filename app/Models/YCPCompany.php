<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YCPCompany extends Model {
    use HasFactory;

    public static function existsInDB( array $row ): bool {
        if ( YCPCompany::query()->where( 'name', '=', $row['name'] )->get()->isNotEmpty() ) {
            return true;
        }

        return false;
    }

    public function contacts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany {
        return $this->belongsToMany( YCPContact::class )->withPivot( [ 'admin', 'contact' ] );
    }

    public function fromCSV( array $row ) {
        $this->name              = $row['name'];
        $this->short_description = $row['short_description'];
        $this->date_joined       = $row['date_joined'];
        $this->expiry_date       = $row['expiry_date'];
        $this->plan              = $row['plan'];
        $this->status            = $row['status'];
        $this->email             = $row['email'];
        $this->website           = $row['website'];
        $this->address_id        = \App\Models\Address::fromCSV( $row )->id;

        $this->save();
        $billing_person = YCPContact::getOrCreateContact( $row['business_person'], $row['business_person_email'] );
        $contact_person = YCPContact::getOrCreateContact( $row['contact_person'], $row['contact_person_email'] );
        $this->contacts()->save( $billing_person, [ 'billing' => true, 'contact' => false ] );
        $this->contacts()->save( $contact_person, [ 'billing' => false, 'contact' => true ] );
    }

    public function address() {
        return $this->morphOne( \App\Models\Address::class, 'addressable' );
    }
}
