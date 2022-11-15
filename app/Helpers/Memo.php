<?php

namespace App\Helpers;

use App\Models\YcpContact;

class Memo {
    private array $contactsByEmail = [];

    public function __construct() {

    }

    public function formContactsByEmail() {
        $contacts = YcpContact::query()->get();
        foreach ( $contacts as $contact ) {
            if ( $contact->hasEmail() && ! isset( $this->contactsByEmail[ $contact->email ] ) ) {
                $this->contactsByEmail[ $contact->email ] = $contact;
            }
        }
    }

    public function findContactByEmail( $email ): YcpContact|bool {
        if ( isset( $this->contactsByEmail[ $email ] ) ) {
            return $this->contactsByEmail[ $email ];
        }

        return false;
    }

    public function appendContact( YcpContact $contact ) {
        if ( $contact->hasEmail() ) {
            $this->contactsByEmail[ $contact->email ] = $contact;
        }
    }

}
