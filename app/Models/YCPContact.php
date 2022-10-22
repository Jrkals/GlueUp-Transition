<?php

namespace App\Models;

use App\Exceptions\ChapterException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class YCPContact extends Model {
    use HasFactory;

    public function chapters(): \Illuminate\Database\Eloquent\Relations\BelongsToMany {
        return $this->belongsToMany( Chapter::class );
    }

    private function __construct( array $attributes = [] ) {
        parent::__construct();
        $this->first_name = $attributes['first_name'];
        $this->last_name  = $attributes['last_name'];
        $this->email      = $attributes['email'];
        $this->nb_tags    = $attributes['nationbuilder_tags'];
        $chapters         = $this->parseChapters( $attributes['active_chapters'] );
        $home_chapter     = Chapter::getOrCreateFromName( $attributes['home_chapter'] );
        $other_chapters   = $this->parseChapters( $attributes['other_chapters'] );
        $this->chapters()->save( $home_chapter );
        $this->chapters()->saveMany( $chapters, [] );
        $this->chapters()->saveMany( $other_chapters, [] );
    }

    public static function fromCSV( array $row ): YCPContact {
        return new YCPContact( $row );
    }

    /**
     * @throws ChapterException
     */
    public function existsInDB(): bool {
        if ( YCPContact::query()->where( 'email', '=', $this->email )->get() ) {
            return true;
        }
        $matchingNames = YCPContact::query()->where( [ 'full_name' => $this->full_name ] )->get();
        foreach ( $matchingNames as $match ) {
            if ( $match->homeChapter()->name === $this->homeChapter()->name ) {
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
            if ( $chapter->home ) {
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
        $chapter_strings = str( $chapters )->split( ',' )->collect();
        foreach ( $chapter_strings as $chapter_string ) {
            $list->add( Chapter::getOrCreateFromName( $chapter_string ) );
        }

        return $list;

    }
}
