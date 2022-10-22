<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Stringable;

class Chapter extends Model {
    use HasFactory;

    public function contacts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany {
        return $this->belongsToMany( YCPContact::class );
    }

    public static function getOrCreateFromName( Stringable $name ) {
        $existing = Chapter::query()->where( 'name', '=', $name->value() )->get();
        if ( ! $existing ) {
            $chapter       = new Chapter();
            $chapter->name = $name->value();
            $chapter->save();

            return $chapter;
        }

        return $existing;
    }
}
