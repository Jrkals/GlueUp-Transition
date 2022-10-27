<?php

namespace App\Models;

use App\Exceptions\ChapterException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class YCPContact extends Model {
    use HasFactory;

    public function chapters(): \Illuminate\Database\Eloquent\Relations\BelongsToMany {
        return $this->belongsToMany( Chapter::class )->withPivot( 'home' );
    }

    public function companies(): \Illuminate\Database\Eloquent\Relations\BelongsToMany {
        return $this->belongsToMany( YCPCompany::class )->withPivot( [ 'admin', 'contact' ] );
    }

    public function fromCSV( array $row ): YCPContact {
        $this->first_name = $row['first_name'];
        $this->last_name  = $row['last_name'];
        $this->full_name  = $row['name'];
        $this->email      = $row['email'];
        $this->nb_tags    = $row['nationbuilder_tags'];
        $chapters         = $this->parseChapters( $row['active_chapters'] );
        $home_chapter     = Chapter::getOrCreateFromName( $row['home_chapter'] );
        $other_chapters   = $this->parseChapters( $row['other_chapters'] );
        $this->save();
        $this->chapters()->save( $home_chapter, [ 'home' => true ] );
        $this->chapters()->saveMany( $chapters, [] );
        $this->chapters()->saveMany( $other_chapters, [] );

        return $this;
    }

    /**
     * @throws ChapterException
     */
    public static function existsInDB( array $contact ): bool {
        if ( YCPContact::query()->where( 'email', '=', $contact['email'] )->get()->isNotEmpty() ) {
            return true;
        }
        $matchingNames = YCPContact::query()->where( [ 'full_name' => $contact['name'] ] )->get();
        foreach ( $matchingNames as $match ) {
            if ( $match->homeChapter()->name === $contact['home_chapter'] ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws ChapterException
     */
    public function homeChapter(): Chapter {
        $chapters = $this->chapters;
        foreach ( $chapters as $chapter ) {
            if ( $chapter->pivot->home ) {
                return $chapter;
            }
        }
        throw ChapterException::NoChapterFound( $this );
    }

    private function parseChapters( string $chapters ): Collection {
        $list = collect( [] );
        if ( empty( $chapters ) ) {
            return $list;
        }
        if ( ! str_contains( $chapters, ',' ) ) {
            return $list->add( Chapter::getOrCreateFromName( $chapters ) );
        }
        $chapter_strings = explode( ",", $chapters );
        foreach ( $chapter_strings as $chapter_string ) {
            $list->add( Chapter::getOrCreateFromName( str( $chapter_string )->trim() ) );
        }

        return $list;

    }
}
