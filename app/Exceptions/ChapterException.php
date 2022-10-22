<?php

namespace App\Exceptions;

use App\Models\YCPContact;
use Exception;

class ChapterException extends Exception {
    public static function NoChapterFound( YCPContact $contact ) {
        return new static ( "No chapter found for " . $contact->id );
    }
}
