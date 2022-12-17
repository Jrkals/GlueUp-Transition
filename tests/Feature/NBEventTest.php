<?php

namespace Tests\Feature;

use App\Helpers\NBTagParser;
use App\Models\YcpContact;
use Carbon\Carbon;
use Tests\TestCase;


class NBEventTest extends TestCase {
    public function test_nb_tag() {
        $tags    = 'signup-completed, shared, 01-07-20-ess-attendee';
        $contact = new YcpContact();
        $parser  = new NBTagParser( $tags, $contact );
        $events  = $parser->makeEvents();
        $this->assertNotEmpty( $events );
        $event = $events[0];
        $this->assertEquals( Carbon::parse( '2020-01-07' )->toDateString(),
            Carbon::parse( $event->date )->toDateString() );
    }
}
