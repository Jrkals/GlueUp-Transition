<?php

use App\Models\Address;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create( 'ycp_contacts', function ( Blueprint $table ) {
            $table->id();
            $table->timestamps();
            $table->string( 'first_name' )->nullable();
            $table->string( 'last_name' )->nullable();
            $table->string( 'full_name' )->nullable();
            $table->string( 'email' )->nullable()->unique();
            $table->longText( 'nb_tags' )->nullable();
            $table->boolean( 'admin' )->default( false );
            $table->foreignIdFor( Address::class )->nullable();
            $table->date( 'date_joined' )->nullable();
            $table->date( 'birthday' )->nullable();
            $table->string( 'title' )->nullable();
            $table->enum( 'subscribed', [
                'Subscribed',
                'Not Subscribed',
                'Unsubscribed'
            ] )->default( 'Not Subscribed' );
            $table->string( 'spiritual_assessment' )->nullable();
            $table->string( 'professional_assessment' )->nullable();
            $table->enum( 't_shirt_size', [ 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', null ] )->nullable()->default( null );
            $table->string( 'virtual_mentoring' )->nullable();
            $table->string( 'years_at_workplace' )->nullable();
            $table->string( 'chapter_interest_list' )->nullable();
            $table->string( 'linkedin' )->nullable();
            $table->string( 'chapter_leader_role' )->nullable();
            $table->longText( 'notes' )->nullable();
            $table->longText( 'bio' )->nullable();
            $table->string( 'industry' )->nullable();
        } );
        Schema::create( 'ycp_companies', function ( Blueprint $table ) {
            $table->id();
            $table->timestamps();
            $table->string( 'name' )->unique();
            $table->string( 'short_description' );
            $table->date( 'date_joined' )->nullable();
            $table->date( 'expiry_date' )->nullable();
            $table->string( 'plan' )->nullable();
            $table->string( 'status' );
            $table->string( 'email' );
            $table->string( 'website' )->nullable();
            $table->string( 'phone' )->nullable();
            $table->longText( 'overview' )->nullable();
            $table->string( 'fax' )->nullable();
            $table->string( 'number_of_employees' )->nullable();
            $table->foreignIdFor( Address::class )->nullable();
        } );
        Schema::create( 'chapters', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'name' );
            $table->timestamps();
        } );
        Schema::create( 'plans', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'name' );
            $table->timestamps();
        } );

        //Many to Many relation table
        Schema::create( 'chapter_ycp_contact', function ( Blueprint $table ) {
            $table->id();
            $table->foreignIdFor( \App\Models\Chapter::class );
            $table->foreignIdFor( \App\Models\YcpContact::class );
            $table->boolean( 'home' )->default( false );
            $table->timestamps();
        } );

        //Many to Many relation table
        Schema::create( 'plan_ycp_contact', function ( Blueprint $table ) {
            $table->id();
            $table->foreignIdFor( \App\Models\Plan::class );
            $table->foreignIdFor( \App\Models\YcpContact::class );
            $table->boolean( 'active' )->default( true );
            $table->date( 'start_date' )->nullable();
            $table->date( 'expiry_date' )->nullable();
            $table->enum( 'expiry_type', [ 'Recurring', 'Manual Renewal', 'Lifetime', 'Unknown' ] )->nullable();
            $table->timestamps();
        } );

        //Many to Many relation table
        Schema::create( 'ycp_company_ycp_contact', function ( Blueprint $table ) {
            $table->id();
            $table->foreignIdFor( \App\Models\YcpContact::class );
            $table->foreignIdFor( \App\Models\YcpCompany::class );
            $table->boolean( 'billing' );
            $table->boolean( 'contact' );
            $table->timestamps();
        } );
        Schema::create( 'addresses', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'street1' );
            $table->string( 'city' )->nullable();
            $table->string( 'state' )->nullable();
            $table->string( 'postal_code' )->nullable();
            $table->string( 'country' );
            $table->integer( 'addressable_id' ); //one to one polymorphic companies and contacts
            $table->string( 'addressable_type' ); //one to one polymorphic companies and contacts
            $table->enum( 'address_type', [ 'home', 'business' ] );
            $table->timestamps();
        } );

        Schema::create( 'phones', function ( Blueprint $table ) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor( \App\Models\YcpContact::class );
            $table->enum( 'type', [ 'home', 'mobile', 'business' ] )->default( 'mobile' );
            $table->string( 'number' );
        } );

        Schema::create( 'ycp_events', function ( Blueprint $table ) {
            $table->id();
            $table->timestamps();
            $table->date( 'date' )->nullable();
            $table->enum( 'type', [ 'ESS', 'Panel', 'Conference', 'NHH', 'SJS', 'Launch', 'Other' ] )
                  ->default( 'Other' );
            $table->string( 'name' );
        } );

        Schema::create( 'ycp_events_contacts', function ( Blueprint $table ) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor( \App\Models\YcpContact::class );
            $table->foreignIdFor( \App\Models\YcpEvent::class );
            $table->boolean( 'attended' )->default( false );
        } );

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists( 'ycp_contacts' );
        Schema::dropIfExists( 'ycp_companies' );
        Schema::dropIfExists( 'plans' );
        Schema::dropIfExists( 'chapters' );
        Schema::dropIfExists( 'chapter_ycp_contact' );
        Schema::dropIfExists( 'plan_ycp_contact' );
        Schema::dropIfExists( 'ycp_company_ycp_contact' );
        Schema::dropIfExists( 'phones' );
        Schema::dropIfExists( 'addresses' );
        Schema::dropIfExists( 'ycp_events' );
        Schema::dropIfExists( 'ycp_events_contacts' );
    }
};
