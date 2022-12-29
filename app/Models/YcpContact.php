<?php

namespace App\Models;

use App\Exceptions\ChapterException;
use App\Helpers\Name;
use App\Helpers\NBTagParser;
use App\Helpers\StringHelpers;
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

    public function events(): \Illuminate\Database\Eloquent\Relations\BelongsToMany {
        return $this->belongsToMany( YcpEvent::class, 'ycp_events_contacts' )->withPivot( 'attended' );
    }

    public function address(): \Illuminate\Database\Eloquent\Relations\MorphOne {
        return $this->morphOne( \App\Models\Address::class, 'addressable' );
    }

    //TODO make this many to many polymorphic or just ignore
//    public function addresses(): \Illuminate\Database\Eloquent\Relations\MorphToMany {
//        return $this->morphToMany( Address::class, 'address' );
//    }

    public function fromCSV( array $row ): YcpContact {
        if ( $this->worthlessContact( $row ) ) {
            return $this; //do nothing and save nothing
        }

        $this->first_name = ! empty( $row['first_name'] ) ? str( $row['first_name'] )->title()->value() : 'Unknown';
        $this->last_name  = ! empty( $row['last_name'] ) ? str( $row['last_name'] )->title()->value() : 'Unknown';
        $this->full_name  = str( $row['name'] )->title()->value();

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
        if ( ! empty( $row['date_of_birth'] ) ) {
            $this->birthday = Carbon::parse( $row['date_of_birth'] )->toDateString();
        }
        $this->subscribed = $row['subscribed'] ?? 'Not Subscribed';
        if ( isset( $row['nationbuilder_tags'] ) && str_contains( strtolower( $row['nationbuilder_tags'] ), 'unsubscribed' )
             || ( isset( $row['notes'] ) && str_contains( $row['notes'], 'Unsubscribed' ) ) ) {
            $this->subscribed = 'Unsubscribed';
        }

        $this->spiritual_assessment    = $row['spiritual_assessment'] ?? '';
        $this->professional_assessment = $row['professional_assessment'] ?? '';
        $this->t_shirt_size            = $row['t_shirt_size'] ?? null;
        $this->virtual_mentoring       = ! empty( $row['would_you_be_open_to_participating_in_virtual_mentoring'] ) ?
            str_contains( $row['would_you_be_open_to_participating_in_virtual_mentoring'], '1' ) ||
            str_contains( $row['would_you_be_open_to_participating_in_virtual_mentoring'], 'TRUE' )
                ? "Yes" : "No"
            : null;
        $this->years_at_workplace      = $row['years_at_current_workplace'] ?? '';
        $this->chapter_interest_list   = $this->formChapterInterestList(
            $row['potential_ycp_city'] ?? '',
            $row['chapter_interest_list'] ?? '' );
        $this->linkedin                = StringHelpers::validateUrl( $row['linkedin_profile'] ?? '' );
        $this->chapter_leader_role     = $row['current_chapter_role_category'] ?? '';
        $this->notes                   = $this->filterNotes( $row['notes'] );
        if ( isset( $row['bio'] ) && StringHelpers::isIndustry( $row['bio'] ) ) {
            $this->industry = $this->mapIndustry( $row['bio'] );
        } else {
            $this->bio = $row['bio'] ?? '';
        }

        if ( ! empty( $row ['plan'] ) ) {
            $currentPlan = Plan::getOrCreatePlan( [
                'name' => $row['plan']
            ] );
        }

        if ( ! empty( $row ['latest_plan'] ) ) {
            $previousPlan = Plan::getOrCreatePlan( [
                'name' => $row['latest_plan']
            ] );
        }

        $home_chapter = Chapter::getOrCreateFromName( $row['home_chapter'] );

        $chapters       = $this->parseChapters( $row['active_chapters'] ?? $row['chapter'] );
        $other_chapters = $this->parseChapters( $row['other_chapters'] );
        $chapters       = $chapters->merge( $other_chapters );

        //Dedup chapters
        $uniqueChapters = [ $home_chapter->name => $home_chapter ];
        foreach ( $chapters as $chapter ) {
            if ( ! isset( $uniqueChapters[ $chapter->name ] ) ) {
                $uniqueChapters[ $chapter->name ] = $chapter;
            }
        }
        unset( $uniqueChapters[ $home_chapter->name ] );
        $uniqueChapters = collect( array_values( $uniqueChapters ) );

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
        if ( isset( $currentPlan ) ) {
            $this->plans()->save( $currentPlan, [
                'active'      => true,
                'expiry_date' => Carbon::parse( $row['expiry_date'] ),
                'expiry_type' => $row['expiry_type'] ?: 'Unknown',
                'start_date'  => $row['last_renewal_date'] ? Carbon::parse( $row['last_renewal_date'] )
                    : Carbon::parse( $row['date_joined'] )
            ] );
        }
        if ( isset( $previousPlan ) && $previousPlan->differentPlan( $currentPlan ?? null ) ) {
            $this->plans()->save( $previousPlan, [
                'active'      => false,
                'expiry_date' => Carbon::parse( $row['expiry_date'] ),
                'expiry_type' => $row['expiry_type'] ?: 'Unknown',
                'start_date'  => Carbon::parse( $row['date_joined'] )
            ] );
        }


        $this->chapters()->save( $home_chapter, [ 'home' => true ] );
        $this->chapters()->saveMany( $uniqueChapters, [] );

        if ( $row['nationbuilder_tags'] ) {
            $nbTagParser = new NBTagParser( $row['nationbuilder_tags'], $this );
            $events      = $nbTagParser->makeEvents();
            $this->events()->saveMany( $events );
        }

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

    public static function getDifferentEmailContact( array $contact ) {
        if ( ! isset( $contact['home_chapter'] ) ) {
            return null;
        }
        $nameMatch = YcpContact::query()->where( 'email', '!=', $contact['email'] )
                               ->where( 'full_name', '=', $contact['name'] )->get();

        foreach ( $nameMatch as $match ) {
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

    public static function getOrCreateContact( string $name, string $email, string $title = '' ): YcpContact {
        $contact = self::getContact( [ 'full_name' => $name, 'email' => $email ] );
        if ( $contact ) {
            if ( $title ) {
                $contact->title = $title;
                $contact->save();
            }

            return $contact;
        }

        //Create a contact with an empty email who will be omitted at export
        if ( ! EmailValidation::emailIsValid( $email ) ) {
            $email = '';
        }

        $name                = Name::fromFullName( $name );
        $contact             = new YcpContact();
        $contact->first_name = $name->firstName();
        $contact->last_name  = $name->lastName();
        $contact->full_name  = $name->fullName();
        $contact->email      = $email ?: null;
        $contact->title      = $title ?: null;
        $contact->save();

        return $contact;
    }

    public function hasEmail(): bool {
        return ! empty( $this->email );
    }

    public static function contactsMatch( array $contact1, YcpContact $contact2 ): array {
        $differences = [
            'any'                     => false,
            'dob'                     => false,
            'mobile_phone'            => false,
            'business_phone'          => false,
            'home_phone'              => false,
            'home_address'            => false,
            'work_address'            => false,
            'nationbuilder'           => false,
            'first_name'              => false,
            'last_name'               => false,
            'subscribed'              => false,
            'spiritual_assessment'    => false,
            'professional_assessment' => false,
            't_shirt_size'            => false,
            'virtual_mentoring'       => false,
            'years_at_workplace'      => false,
            'chapter_interest_list'   => false,
            'linkedin'                => false,
            'chapter_leader_role'     => false,
            'notes'                   => false,
            'bio'                     => false,
            'id'                      => $contact2->id
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
        $a = empty( $contact1['mobile_phone'] );
        $b = $contact2->hasPhone( $contact1 );
        if ( ! empty( $contact1['mobile_phone'] ) && ! $contact2->hasPhone( $contact1 ) ) {
            $differences['any']                 = true;
            $differences['mobile_phone']        = true;
            $differences['mobile_phone_reason'] = 'missing mobile phone number ' . $contact1['mobile_phone'];
        }
        if ( ! empty( $contact1['business_phone'] ) && ! $contact2->hasPhone( $contact1 ) ) {
            $differences['any']                   = true;
            $differences['business_phone']        = true;
            $differences['business_phone_reason'] = 'missing business phone number ' . $contact1['business_phone'];
        }
        if ( ! empty( $contact1['home_phone'] ) && ! $contact2->hasPhone( $contact1 ) ) {
            $differences['any']               = true;
            $differences['home_phone']        = true;
            $differences['home_phone_reason'] = 'missing home phone number ' . $contact1['home_phone'];
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

        if ( ! empty( $contact1['subscribed'] ) && $contact1['subscribed'] !== $contact2->subscribed ) {
            $differences['any']               = true;
            $differences['subscribed']        = true;
            $differences['subscribed_reason'] = $contact1['subscribed'] . ' is not ' . $contact2->subscribed;
        }

        if ( ! empty( $contact1['spiritual_assessment'] ) && $contact1['spiritual_assessment'] !== $contact2->spiritual_assessment ) {
            $differences['any']                         = true;
            $differences['spiritual_assessment']        = true;
            $differences['spiritual_assessment_reason'] = $contact1['spiritual_assessment'] . ' is not ' . $contact2->spiritual_assessment;
        }

        if ( ! empty( $contact1['professional_assessment'] ) && $contact1['professional_assessment'] !== $contact2->professional_assessment ) {
            $differences['any']                            = true;
            $differences['professional_assessment']        = true;
            $differences['professional_assessment_reason'] = $contact1['professional_assessment'] . ' is not ' . $contact2->professional_assessment;
        }

        if ( ! empty( $contact1['t_shirt_size'] ) && $contact1['t_shirt_size'] !== $contact2->t_shirt_size ) {
            $differences['any']                 = true;
            $differences['t_shirt_size']        = true;
            $differences['t_shirt_size_reason'] = $contact1['t_shirt_size'] . ' is not ' . $contact2->t_shirt_size;
        }

        if ( ! empty( $contact1['virtual_mentoring'] ) && $contact1['virtual_mentoring'] !== $contact2->virtual_mentoring ) {
            $differences['any']                      = true;
            $differences['virtual_mentoring']        = true;
            $differences['virtual_mentoring_reason'] = $contact1['virtual_mentoring'] . ' is not ' . $contact2->virtual_mentoring;
        }

        if ( ! empty( $contact1['years_at_current_workplace'] ) && $contact1['years_at_current_workplace'] !== $contact2->years_at_workplace ) {
            $differences['any']                       = true;
            $differences['years_at_workplace']        = true;
            $differences['years_at_workplace_reason'] = $contact1['years_at_current_workplace'] . ' is not ' . $contact2->years_at_workplace;
        }

        if ( ! empty( $contact1['chapter_interest_list'] ) && $contact1['chapter_interest_list'] !== $contact2->chapter_interest_list ) {
            $differences['any']                          = true;
            $differences['chapter_interest_list']        = true;
            $differences['chapter_interest_list_reason'] = $contact1['chapter_interest_list'] . ' is not ' . $contact2->chapter_interest_list;
        }

        if ( ! empty( $contact1['linkedin'] ) && $contact1['linkedin'] !== $contact2->linkedin ) {
            $differences['any']             = true;
            $differences['linkedin']        = true;
            $differences['linkedin_reason'] = $contact1['linkedin'] . ' is not ' . $contact2->linkedin;
        }

        if ( ! empty( $contact1['notes'] ) && $contact1['notes'] !== $contact2->notes ) {
            $differences['any']          = true;
            $differences['notes']        = true;
            $differences['notes_reason'] = $contact1['notes'] . ' is not ' . $contact2->notes;
        }

        if ( ! empty( $contact1['bio'] ) && $contact1['bio'] !== $contact2->bio ) {
            $differences['any']        = true;
            $differences['bio']        = true;
            $differences['bio_reason'] = $contact1['bio'] . ' is not ' . $contact2->bio;
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
        if ( $differences['spiritual_assessment'] ) {
            $contact2->spiritual_assessment = $contact1['spiritual_assessment'];
        }
        if ( $differences['professional_assessment'] ) {
            $contact2->professional_assessment = $contact1['professional_assessment'];
        }
        if ( $differences['t_shirt_size'] ) {
            $contact2->t_shirt_size = $contact1['t_shirt_size'];
        }
        if ( $differences['virtual_mentoring'] ) {
            $contact2->virtual_mentoring = $contact1['virtual_mentoring'];
        }
        if ( $differences['years_at_workplace'] ) {
            $contact2->years_at_workplace = $contact1['years_at_current_workplace'];
        }
        if ( $differences['chapter_interest_list'] ) {
            $contact2->chapter_interest_list = $contact1['chapter_interest_list'];
        }
        if ( $differences['linkedin'] ) {
            $contact2->linkedin = $contact1['linkedin'];
        }
        if ( $differences['chapter_leader_role'] ) {
            $contact2->chapter_leader_role = $contact1['chapter_leader_role'];
        }
        if ( $differences['notes'] ) {
            $contact2->notes = $contact1['notes'];
        }
        if ( $differences['bio'] ) {
            $contact2->bio = $contact1['bio'];
        }
        $contact2->save();

        return $contact2;
    }

    public function hasPhone( array $csvRow ): bool {
        if ( empty( $csvRow['mobile_phone'] ) && empty( $csvRow['business_phone'] ) && empty( $csvRow['home_phone'] ) ) {
            return true;
        }
        $csvPhones   = [];
        $csvPhones[] = $csvRow['mobile_phone'] ?? null;
        $csvPhones[] = $csvRow['business_phone'] ?? null;
        $csvPhones[] = $csvRow['home_phone'] ?? null;

        if ( empty( $this->phones ) ) {
            return false;
        }

        foreach ( $csvPhones as $cphone ) {
            $found = false;
            if ( ! $cphone ) {
                continue;
            }
            foreach ( $this->phones as $phone ) {
                if ( $phone->sameAs( $cphone ) ) {
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
            'addressable_id'   => $this->id,
            'address_type'     => 'home'
        ] )->first();
    }

    public function compileEventInfo(): string {
        if ( $this->events->isEmpty() ) {
            return '';
        }
        $eventInfo = []; // [Year -> [Events]]
        foreach ( $this->events as $event ) {
            if ( $event->date ) {
                $year                 = Carbon::parse( $event->date )->year;
                $eventInfo[ $year ][] = $event->name;
            }
        }
        $formattedString = '';
        foreach ( $eventInfo as $year => $events ) {
            $formattedString .= "\n" . $year . "\n";
            foreach ( $events as $eventName ) {
                $formattedString .= $eventName . ",";
            }
        }

        return $formattedString;
    }

    /**
     * @param array $row
     * Returns
     *
     * @return bool true if the row has no email, no phone, and no chapter
     */
    private function worthlessContact( array $row ): bool {
        return empty( $row['email'] ) && empty( $row['mobile_phone'] ) && empty( $row['home_chapter'] );
    }

    private function formChapterInterestList( string $potentialCity, string $chapterInterestList ): string {
        if ( empty( $potentialCity ) && empty( $chapterInterestList ) ) {
            return '';
        }
        if ( empty( $chapterInterestList ) ) {
            return $this->mapPotentialCity( $potentialCity );
        }

        return $chapterInterestList;
    }

    private function mapPotentialCity( string $potentialCity ): string {
        $potentialCity = explode( ',', $potentialCity, )[0];

        return match ( $potentialCity ) {
            'Albuquerque/Rio Rancho' => 'Albuquerque',
            'Bismark', 'Calgary', 'Monterrey', 'D.C.', 'Oklahoma City', 'Ottawa', 'Twin Cities' => $potentialCity,
            default => '',
        };
    }

    private function filterNotes( string $notes ): string {
        if ( $notes === '3-16-20 member import' ) {
            return '';
        }
        if ( str_contains( $notes, 'Upload' ) ) {
            return '';
        }
        if ( str_contains( $notes, 'Unsubscribed' ) ) {
            return '';
        }

        return $notes;
    }

    private function mapIndustry( string $industry ): string {
        return match ( $industry ) {
            'Finance/Accounting' => 'ACCT',
            'Non-profit' => 'NPRMGT',
            'Operations & Logistics' => 'LOGIST',
            'Real Estate' => 'COMMER',
            'Software Developer', 'Software Engineer' => 'COMPSW',
            'Television & Media' => 'TELCO',
            'Marketing & Advertising' => 'MKTADV',
            'Legal' => 'LAW',
            'Insurance' => 'INSRCE',
            'Information Technology' => 'COMPSC',
            'Human Resources' => 'HR',
            'Health Care', 'Healthcare' => 'HEALTH',
            'Government' => 'GOVT',
            'Financial Planning' => 'FINSER',
            'Fashion & Design' => 'FASHN',
            'Engineering' => 'MECHAN', // COULD BE CIVIL TOO
            'Energy' => 'ENERGY',
            'Education' => 'EDU',
            'Communications' => 'PUBREL',
            default => 'OT' //OTHER
        };
    }

    public function activeChapterLeader(): bool {
        if ( $this->plans->isEmpty() ) {
            return false;
        }
        foreach ( $this->plans as $plan ) {
            if ( $plan->name === 'Chapter Leader' && $plan->pivot->active ) {
                return true;
            }
        }

        return false;
    }

    public function mergeIn( YcpContact $contact ) {
        echo "merging in $contact->email with $this->email \n";
        //plans
        foreach ( $contact->plans as $plan ) {
            //TODO check duplicates
            $this->plans()->save( $plan, [
                'active'      => $plan->pivot->active, //context should mean this is always false
                'expiry_date' => $plan->pivot->expiry_date,
                'expiry_type' => $plan->pivot->expiry_type,
                'start_date'  => $plan->pivot->start_date,
            ] );
        }
        //phones
        if ( $this->phones->isEmpty() && $contact->phones->isNotEmpty() ) {
            $this->phones->saveMany( $contact->phones ); //does this work?
        }

        //events
        if ( $this->events->isEmpty() && $contact->events->isNotEmpty() ) {
            $this->events->saveMany( $contact->events ); //does this work?
        }
        //addresses
        if ( $contact->address_id && ! isset( $this->address_id ) ) {
            $this->address_id = $contact->address_id;
        }
        //companies
        if ( $this->companies->isEmpty() && $contact->companies->isNotEmpty() ) {
            $this->companies->saveMany( $contact->companies ); //does this work?
        }

        //bio
        if ( ! isset( $this->bio ) ) {
            $this->bio = $contact->bio;
        }

        //industry
        if ( ! isset( $this->industry ) ) {
            $this->industry = $contact->industry;
        }

        //linkedin
        if ( ! isset( $this->linkedin ) && $contact->linkedin ) {
            $this->linkedin = $contact->linkedin;
        }

        $this->save();
        $contact->delete();
    }
}
