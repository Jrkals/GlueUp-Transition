<?php

namespace App\Exceptions;

use App\Models\YcpContact;
use Exception;

class ChapterException extends Exception {
    public static function NoChapterFound( YcpContact $contact ) {
        return new static ( "No chapter found for " . $contact->id . ' ' . $contact->full_name );
    }

    public static function NoPlanFound( int $id, int $plan_id ) {
        return new static ( 'No Plan for contact ' . $id . ' for plan ' . $plan_id );
    }
}
