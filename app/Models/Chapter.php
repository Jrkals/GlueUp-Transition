<?php

namespace App\Models;

use App\Exceptions\ChapterException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Stringable;

class Chapter extends Model {
    use HasFactory;

    public function contacts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany {
        return $this->belongsToMany( YcpContact::class )->withPivot( 'home' );
    }

    public static function getOrCreateFromName( string $name ) {
        $existing = Chapter::query()->where( 'name', '=', $name )->get();
        if ( $existing->isEmpty() ) {
            $chapter       = new Chapter();
            $chapter->name = $name;
            $chapter->save();

            return $chapter;
        }

        return $existing->first();
    }

    /**
     * @throws ChapterException
     */
    public function glueUpId(): int {
        if ( $id = config( 'services.glueup.chapters.' . $this->name ) ) {
            return $id;
        }

        return 0;
        //   throw ChapterException::NoChapterMappingFound( $this );
    }
}
