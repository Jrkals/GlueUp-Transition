<?php

namespace App\Models;

use App\Exceptions\ChapterException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chapter extends Model {
    use HasFactory;

    public function contacts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany {
        return $this->belongsToMany( YcpContact::class )->withPivot( 'home' );
    }

    public static function getOrCreateFromName( string $name ) {
        $mapped_name = str_replace( '.', '', $name ) ?: 'Young Catholic Professionals';
        $existing    = Chapter::query()->where( 'name', '=', $mapped_name )->get();
        if ( $existing->isEmpty() ) {
            $chapter       = new Chapter();
            $chapter->name = $mapped_name;
            $chapter->save();

            return $chapter;
        }

        return $existing->first();
    }

    /**
     * @throws ChapterException
     */
    public function glueUpId() {
        if ( $id = config( 'services.glueup.chapters.' . $this->name ) ) {
            return $id;
        }

        return '';
    }
}
