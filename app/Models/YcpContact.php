<?php

namespace App\Models;

use App\Exceptions\ChapterException;
use App\Helpers\Name;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class YcpContact extends Model {
    use HasFactory;

    protected $fillable = [
        'birthday'
    ];

    public function chapters(): \Illuminate\Database\Eloquent\Relations\BelongsToMany {
        return $this->belongsToMany( Chapter::class )->withPivot( 'home' );
    }

    public function companies(): \Illuminate\Database\Eloquent\Relations\BelongsToMany {
        return $this->belongsToMany( YcpCompany::class )->withPivot( [ 'admin', 'contact' ] );
    }

    public function plans(): \Illuminate\Database\Eloquent\Relations\BelongsToMany {
        return $this->belongsToMany( Plan::class )->withPivot( 'active' );
    }

    public function phones(): \Illuminate\Database\Eloquent\Relations\HasMany {
        return $this->hasMany( Phone::class );
    }

    public function fromCSV( array $row ): YcpContact {
        $this->first_name = $row['first_name'];
        $this->last_name  = $row['last_name'];
        $this->full_name  = $row['name'];
        if ( ! empty( $row['email'] ) ) {
            $this->email = ! empty( $row['email'] );
        }
        $this->nb_tags     = $row['nationbuilder_tags'];
        $this->admin       = isset( $row['chapter_admin'] ) && $row['chapter_admin'] === 'TRUE';
        $this->date_joined = Carbon::parse( $row['date_joined'] )->toDateString();
        $this->expiry_date = Carbon::parse( $row['expiry_date'] )->toDateString();
        if ( $row['expiry_type'] ) {
            $this->expiry_type = $row['expiry_type'];
        }
        if ( isset( $row['date_of_birth'] ) ) {
            $this->birthday = Carbon::parse( $row['date_of_birth'] )->toDateString();
        }

        $currentPlan    = Plan::getOrCreateFromName( $row['plan'] );
        $previousPlan   = Plan::getOrCreateFromName( $row['last_renewed_plan'] );
        $chapters       = $this->parseChapters( $row['active_chapters'] );
        $home_chapter   = Chapter::getOrCreateFromName( $row['home_chapter'] );
        $other_chapters = $this->parseChapters( $row['other_chapters'] );
        $this->save();

        if ( $row['mobile_phone'] ) {
            Phone::create( $row['mobile_phone'], $this->id, 'mobile' );
        }
        if ( $row['business_phone'] ) {
            Phone::create( $row['business_phone'], $this->id, 'business' );
        }
        if ( $row['home_phone'] ) {
            Phone::create( $row['home_phone'], $this->id, 'home' );
        }

        $this->plans()->save( $currentPlan, [ 'active' => $row['status'] === 'Active' ] );
        if ( $previousPlan->differentPlan( $currentPlan ) ) {
            $this->plans()->save( $previousPlan, [ 'active' => false ] );
        }
        $this->chapters()->save( $home_chapter, [ 'home' => true ] );
        $this->chapters()->saveMany( $chapters, [] );
        $this->chapters()->saveMany( $other_chapters, [] );

        if ( ! empty( $row ['home_street_address'] ) ) {
            $this->address_id = Address::fromCSVHome( $row, 'contact', $this->id )->id;
            $this->save();
        }
        if ( ! empty( $row ['work_street_address'] ) ) {
            $this->address_id = Address::fromCSVWork( $row, 'contact', $this->id )->id;
            $this->save();
        }


        return $this;
    }

    public static function getContact( array $contact ): ?YcpContact {
        if ( ! empty( $contact['email'] ) ) {
            $emailMatch = YcpContact::query()->where( 'email', '=', $contact['email'] )->get()->first();

            if ( $emailMatch ) {
                return $emailMatch;
            }
        }

        if ( ! isset( $contact['home_chapter'] ) ) {
            return null;
        }

        $matchingNames = YcpContact::query()->where( [ 'full_name' => $contact['name'] ] )->get();
        foreach ( $matchingNames as $match ) {
            if ( $match->homeChapter()?->name === $contact['home_chapter'] ) {
                return $match;
            }
        }

        return null;
    }

    /**
     * @throws ChapterException
     */
    public function homeChapter(): ?Chapter {
        $chapters = $this->chapters;
        foreach ( $chapters as $chapter ) {
            if ( $chapter->pivot->home ) {
                return $chapter;
            }
        }

        return null;
        //  throw ChapterException::NoChapterFound( $this );
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

    public static function getOrCreateContact( string $name, string $email ): YcpContact {
        $contact = self::getContact( [ 'name' => $name, 'email' => $email ] );
        if ( $contact ) {
            return $contact;
        }
        $name                = Name::fromFullName( $name );
        $contact             = new YcpContact();
        $contact->first_name = $name->firstName();
        $contact->last_name  = $name->lastName();
        $contact->full_name  = $name->fullName();
        $contact->email      = $email;
        $contact->save();

        return $contact;
    }

    public function address() {
        return $this->morphOne( \App\Models\Address::class, 'addressable' );
    }

    public static function contactsMatch( array $contact1, YcpContact $contact2 ): array {
        $differences = [
            'any' => false,
            'dob' => false
        ];

        if ( ! empty( $contact1['date_of_birth'] ) && ! Carbon::parse( $contact1['date_of_birth'] )->isSameDay( Carbon::parse( $contact2->birthday ) ) ) {
            $differences['any']        = true;
            $differences['dob']        = true;
            $differences['dob_reason'] = $contact1['date_of_birth'] . ' is DOB not ' . $contact2->birthday;
        }

        //TODO compare other attributes

        return $differences;
    }

    public static function updateContact( array $contact1, YcpContact $contact2, array $differences ): YcpContact {
        if ( $differences['dob'] ) {
            echo "1." . $contact2->birthday . "\n";
            $contact2->birthday = Carbon::parse( $contact1['date_of_birth'] )->toDateString();
            echo "2." . $contact2->birthday . "\n";

        }

        $contact2->update( [ 'birthday' => Carbon::parse( $contact1['date_of_birth'] )->toDateString() ] );
        echo "3." . $contact2->birthday . "\n";

        return $contact2;
    }
}
