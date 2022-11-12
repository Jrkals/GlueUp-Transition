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
        return $this->belongsToMany( YcpCompany::class )->withPivot( [ 'billing', 'contact' ] );
    }

    public function plans(): \Illuminate\Database\Eloquent\Relations\BelongsToMany {
        return $this->belongsToMany( Plan::class )->withPivot( 'active', 'start_date', 'expiry_date' );
    }

    public function phones(): \Illuminate\Database\Eloquent\Relations\HasMany {
        return $this->hasMany( Phone::class );
    }

    //TODO make this many to many polymorphic or just ignore
//    public function addresses(): \Illuminate\Database\Eloquent\Relations\MorphToMany {
//        return $this->morphToMany( Address::class, 'address' );
//    }

    public function fromCSV( array $row ): YcpContact {
        $this->first_name = $row['first_name'] ?? '';
        $this->last_name  = $row['last_name'] ?? '';
        $this->full_name  = $row['name'];

        if ( $this->full_name && ! $this->first_name && ! $this->last_name ) {
            $this->first_name = Name::fromFullName( $this->full_name )->firstName();
            $this->last_name  = Name::fromFullName( $this->full_name )->lastName();
        }

        if ( ! empty( $row['email'] ) ) {
            $this->email = $row['email'];
        }
        $this->nb_tags     = $row['nationbuilder_tags'] ?? '';
        $this->admin       = isset( $row['chapter_admin'] ) && $row['chapter_admin'] === 'TRUE';
        $this->date_joined = Carbon::parse( $row['date_joined'] )->toDateString();
        if ( isset( $row['date_of_birth'] ) ) {
            $this->birthday = Carbon::parse( $row['date_of_birth'] )->toDateString();
        }

        $currentPlan = Plan::getOrCreatePlan( [
            'name' => $row['plan']
        ] );
        if ( ! empty( $row ['last_renewed_plan'] ) ) {
            $previousPlan = Plan::getOrCreatePlan( [
                'name' => $row['last_renewed_plan']
            ] );
        }

        $chapters       = $this->parseChapters( $row['active_chapters'] ?? $row['chapter'] );
        $home_chapter   = Chapter::getOrCreateFromName( $row['home_chapter'] );
        $other_chapters = $this->parseChapters( $row['other_chapters'] );
        $this->save();

        if ( ! empty( $row['mobile_phone'] ) ) {
            Phone::create( $row['mobile_phone'], $this->id, 'mobile' );
        }
        if ( ! empty( $row['business_phone'] ) ) {
            Phone::create( $row['business_phone'], $this->id, 'business' );
        }
        if ( ! empty( $row['home_phone'] ) ) {
            Phone::create( $row['home_phone'], $this->id, 'home' );
        }
        if ( $row['status'] !== 'Contact' ) {
            $this->plans()->save( $currentPlan, [
                'active'      => true,
                'expiry_date' => Carbon::parse( $row['expiry_date'] ),
                'expiry_type' => $row['expiry_type'] ?: 'Unknown',
                'start_date'  => $row['last_renewal_date'] ? Carbon::parse( $row['last_renewal_date'] )
                    : Carbon::parse( $row['date_joined'] )
            ] );
            if ( isset( $previousPlan ) && $previousPlan->differentPlan( $currentPlan ) ) {
                $this->plans()->save( $previousPlan, [
                    'active'      => false,
                    'expiry_date' => Carbon::parse( $row['expiry_date'] ),
                    'expiry_type' => $row['expiry_type'] ?: 'Unknown',
                    'start_date'  => Carbon::parse( $row['date_joined'] )
                ] );
            }
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

    /**
     * @param array $contact
     * Returns an email match if found. If an email is given and no match is found, null is returned
     * Returns a match by name and chapter if no email is given.
     *
     * @return YcpContact|null
     */
    public static function getContact( array $contact ): ?YcpContact {
        if ( ! empty( $contact['email'] ) ) {
            $emailMatch = YcpContact::query()->where( 'email', '=', $contact['email'] )->get()->first();

            if ( $emailMatch ) {
                return $emailMatch;
            }

            return null;
        }

        if ( ! isset( $contact['home_chapter'] ) ) {
            return null;
        }

        $matchingNames = YcpContact::query()->where( [ 'full_name' => $contact['name'] ] )->get();
        foreach ( $matchingNames as $match ) {
            if ( $match->homeChapter()?->name === $contact['home_chapter'] ) {
                return $match;
            }
            //Match same name both empty emails
            if ( ! $match->email && ! $contact['email'] ) {
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

    /**
     * @return string non home chapters in an array
     */
    public function chapterIds(): string {
        $return = '';
        foreach ( $this->chapters as $chapter ) {
            if ( $chapter->pivot->home ) {
                continue;
            }
            $return .= $chapter->glueUpId() . ',';
        }

        return str( $return )->trim( ',' )->value();
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
        $contact->email      = $email ?: null;
        $contact->save();

        return $contact;
    }

    public function address() {
        return $this->morphOne( \App\Models\Address::class, 'addressable' );
    }

    public static function contactsMatch( array $contact1, YcpContact $contact2 ): array {
        $differences = [
            'any'            => false,
            'dob'            => false,
            'mobile_phone'   => false,
            'business_phone' => false,
            'home_phone'     => false,
            'home_address'   => false,
            'work_address'   => false,
            'nationbuilder'  => false,
            'first_name'     => false,
            'last_name'      => false,
            'id'             => $contact2->id
        ];

        if ( ! empty( $contact1['date_of_birth'] ) && ! Carbon::parse( $contact1['date_of_birth'] )->isSameDay( Carbon::parse( $contact2->birthday ) ) ) {
            $differences['any']        = true;
            $differences['dob']        = true;
            $differences['dob_reason'] = $contact1['date_of_birth'] . ' is DOB not ' . $contact2->birthday;
        }

        if ( ! empty( $contact1['nationbuilder_tags'] ) && $contact1['nationbuilder_tags'] !== $contact2->nb_tags ) {
            $differences['any']                  = true;
            $differences['nationbuilder']        = true;
            $differences['nationbuilder_reason'] = $contact1['nationbuilder_tags'] . ' is not ' . $contact2->nb_tags;
        }
        //Phones
        if ( ! empty( $contact1['mobile_number'] ) && ! $contact2->hasPhone( $contact1 ) ) {
            $differences['any']                 = true;
            $differences['mobile_phone']        = true;
            $differences['mobile_phone_reason'] = 'missing mobile phone number ' . $contact1['mobile_number'];
        }
        if ( ! empty( $contact1['business_number'] ) && ! $contact2->hasPhone( $contact1 ) ) {
            $differences['any']                   = true;
            $differences['business_phone']        = true;
            $differences['business_phone_reason'] = 'missing business phone number ' . $contact1['mobile_number'];
        }
        if ( ! empty( $contact1['home_number'] ) && ! $contact2->hasPhone( $contact1 ) ) {
            $differences['any']               = true;
            $differences['home_phone']        = true;
            $differences['home_phone_reason'] = 'missing home phone number ' . $contact1['mobile_number'];
        }

        //Addresses
        if ( ! empty( $contact1['home_city'] ) && ! $contact2->hasAddress( $contact1 ) ) {
            $differences['any']                = true;
            $differences['home_address']       = true;
            $differences['home_adress_reason'] = 'missing home address ' . $contact1['home_city'];
        }
        if ( ! empty( $contact1['work_city'] ) && ! $contact2->hasAddress( $contact1 ) ) {
            $differences['any']                = true;
            $differences['work_address']       = true;
            $differences['work_adress_reason'] = 'missing work address ' . $contact1['work_city'];
        }

        if ( ! empty( $contact1['first_name'] ) && strtolower( $contact1['first_name'] )
                                                   !== strtolower( $contact2->first_name ) ) {
            $differences['any']               = true;
            $differences['first_name']        = true;
            $differences['first_name_reason'] = $contact1['first_name'] . ' is not ' . $contact2->first_name;
        }

        if ( ! empty( $contact1['last_name'] ) && strtolower( $contact1['last_name'] )
                                                  !== strtolower( $contact2->last_name ) ) {
            $differences['any']              = true;
            $differences['last_name']        = true;
            $differences['last_name_reason'] = $contact1['last_name'] . ' is not ' . $contact2->last_name;
        }

        return $differences;
    }

    public static function updateContact( array $contact1, YcpContact $contact2, array $differences ): YcpContact {
        if ( $differences['dob'] ) {
            $contact2->birthday = Carbon::parse( $contact1['date_of_birth'] )->toDateString();
        }
        if ( $differences['nationbuilder'] ) {
            $contact2->nb_tags = $contact1['nationbuilder_tags'];
        }
        if ( $differences['mobile_phone'] ) {
            $contact2->phones()->save( Phone::create( $contact1['mobile_phone'], $contact2->id, 'mobile' ) );
        }
        if ( $differences['business_phone'] ) {
            $contact2->phones()->save( Phone::create( $contact1['business_phone'], $contact2->id, 'business' ) );
        }
        if ( $differences['home_phone'] ) {
            $contact2->phones()->save( Phone::create( $contact1['home_phone'], $contact2->id, 'home' ) );
        }
        if ( $differences['home_address'] ) {
            $contact2->address()->save( Address::fromCSVHome( $contact1, 'contact', $contact2->id ) );
        }
        if ( $differences['work_address'] ) {
            $contact2->address()->save( Address::fromCSVWork( $contact1, 'contact', $contact2->id ) );
        }
        if ( $differences['first_name'] ) {
            $contact2->first_name = $contact1['first_name'];
        }
        if ( $differences['last_name'] ) {
            $contact2->last_name = $contact1['last_name'];
        }
        $contact2->save();

        return $contact2;
    }

    public function hasPhone( array $csvRow ): bool {
        if ( empty( $csvRow['mobile_number'] ) && empty( $csvRow['business_number'] ) && empty( $csvRow['home_number'] ) ) {
            return true;
        }
        $csvPhones   = [];
        $csvPhones[] = $csvRow['mobile_number'] ?? null;
        $csvPhones[] = $csvRow['business_number'] ?? null;
        $csvPhones[] = $csvRow['home_number'] ?? null;

        if ( empty( $this->phones ) ) {
            return false;
        }

        foreach ( $csvPhones as $cphone ) {
            $found = false;
            foreach ( $this->phones as $phone ) {
                if ( $cphone->sameAs( $phone ) ) {
                    $found = true;
                }
            }
            if ( ! $found ) {
                return false;
            }
        }

        return true;
    }

    public function hasAddress( array $csvRow ): bool {
        if ( empty( $csvRow['home_city'] ) && empty( $csvRow['work_city'] ) ) {
            return true;
        }

        //TODO perhaps replace with relationship many to many polymorphic
        $addresses = Address::query()->where( [
            'addressable_id'   => $this->id,
            'addressable_type' => YcpContact::class
        ] )->get();

        $homeAddress = Address::makeHomeAddressDto( $csvRow );
        $workAddress = Address::makeWorkAddressDto( $csvRow );

        foreach ( $addresses as $address ) {
            if ( $address->isSame( $homeAddress ) ) {
                return true;
            }
        }
        foreach ( $addresses as $address ) {
            if ( $address->isSame( $workAddress ) ) {
                return true;
            }
        }

        return false;
    }

    public function workPhone(): ?Phone {
        if ( $this->phones->isEmpty() ) {
            return null;
        }
        foreach ( $this->phones as $phone ) {
            if ( $phone->type === 'business' ) {
                return $phone;
            }
        }

        return $this->phones->first();
    }

    public function primaryPhone(): ?Phone {
        if ( $this->phones->isEmpty() ) {
            return null;
        }
        foreach ( $this->phones as $phone ) {
            if ( $phone->type === 'mobile' ) {
                return $phone;
            }
        }

        return $this->phones->first();
    }

    public function companyName(): string {
        if ( $this->companies->isEmpty() ) {
            return '';
        }

        return $this->companies->first()->name;
    }

    public function getPlan( $plan_id ): Plan {
        foreach ( $this->plans as $plan ) {
            if ( $plan->id === $plan_id ) {
                return $plan;
            }
        }
        throw ChapterException::NoPlanFound( $this->id, $plan_id );
    }

    public function billingAddress(): ?Address {
        return Address::query()->where( [
            'addressable_type' => YcpContact::class,
            'addressable_id'   => $this->id
        ] )->first();
    }
}
