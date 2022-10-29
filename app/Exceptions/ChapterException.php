<?php

namespace App\Exceptions;

use App\Models\YcpContact;
use Exception;

class ChapterException extends Exception {
    public static function NoChapterFound( YcpContact $contact ) {
        return new static ( "No chapter found for " . $contact->id );
    }
}
