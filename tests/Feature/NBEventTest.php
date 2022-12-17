<?php

namespace Tests\Feature;

use App\Helpers\NBTagParser;
use App\Models\YcpContact;
use Carbon\Carbon;
use Tests\TestCase;


class NBEventTest extends TestCase {

    public function test_nb_tag_dashes_ess() {
        $tags    = 'signup-completed, shared, 01-07-20-ess-attendee';
        $contact = new YcpContact();
        $parser  = new NBTagParser( $tags, $contact );
        $events  = $parser->makeEvents();
        $this->assertNotEmpty( $events );
        $event = $events[0];
        $this->assertEquals( Carbon::parse( '2020-01-07' )->toDateString(),
            Carbon::parse( $event->date )->toDateString() );
    }

    public function test_nb_tag_spaces_ess() {
        $tags    = 'signup-completed, shared, 2019 Mar ESS';
        $contact = new YcpContact();
        $parser  = new NBTagParser( $tags, $contact );
        $events  = $parser->makeEvents();
        $this->assertNotEmpty( $events );
        $event = $events[0];
        $this->assertEquals( Carbon::parse( '2019-01-01' )->toDateString(),
            Carbon::parse( $event->date )->toDateString() );
        $this->assertEquals( 'ESS', $event->type );
    }

    public function test_nb_tag_underscore_nhh() {
        $tags    = 'signup-completed, shared, 2019_Jan_NHH';
        $contact = new YcpContact();
        $parser  = new NBTagParser( $tags, $contact );
        $events  = $parser->makeEvents();
        $this->assertNotEmpty( $events );
        $event = $events[0];
        $this->assertEquals( Carbon::parse( '2019-01-01' )->toDateString(),
            Carbon::parse( $event->date )->toDateString() );
        $this->assertEquals( 'NHH', $event->type );
    }

    public function test_nb_tag_long_list() {
        $tags    = 'shared, signup-completed, RSVP-ESS-4/5/16, ESS-9-6-16-Checked-In V2, 161004-ess-noshow, 170103-ess-attended, 160906-ess-attended, 170502-ess-attended, 160405-ess-noshow, April2016HHAttended, May2016ESSNoShow, 170912-ess-rsvp, 170912-ess-noshow, 171205-ess-rsvp, 171205-ess-attended, 180109-ess-rsvp, 180109-ess-attended, 180306-ess-attended, 180725-panel-rsvp, 180725-panel-attended, 180807-august-ess-waitlist, 180911-sept-ess-rsvp, 180911-sept-ess-attended, 181002-oct-ess-rsvp, 181002-oct-ess-attended, great_books_signup, great books';
        $contact = new YcpContact();
        $parser  = new NBTagParser( $tags, $contact );
        $events  = $parser->makeEvents();
        $this->assertNotEmpty( $events );
        $event = $events[0];
        $this->assertEquals( Carbon::parse( '2016-04-05' )->toDateString(),
            Carbon::parse( $event->date )->toDateString() );
        $this->assertEquals( 'ESS', $event->type );
    }

}
