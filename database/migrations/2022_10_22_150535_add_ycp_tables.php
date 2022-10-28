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
        Schema::create( 'y_c_p_contacts', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'first_name' )->nullable();
            $table->string( 'last_name' )->nullable();
            $table->string( 'full_name' )->nullable();
            $table->string( 'email' )->nullable()->unique();
            $table->longText( 'nb_tags' )->nullable();
            $table->string( 'plan' );
            $table->foreignIdFor( Address::class )->nullable();
            $table->timestamps();
        } );
        Schema::create( 'y_c_p_companies', function ( Blueprint $table ) {
            $table->id();
            $table->timestamps();
            $table->string( 'name' )->unique();
            $table->string( 'short_description' );
            $table->date( 'date_joined' );
            $table->date( 'expiry_date' );
            $table->string( 'plan' );
            $table->string( 'status' );
            $table->string( 'email' );
//            $table->integer( 'billing_person_id' ); // foreign key
//            $table->integer( 'contact_person_id' ); // foreign key
            $table->string( 'website' )->nullable();
            $table->foreignIdFor( Address::class );
        } );
        Schema::create( 'chapters', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'name' );
            $table->timestamps();
        } );
        Schema::create( 'chapter_y_c_p_contact', function ( Blueprint $table ) {
            $table->id();
            $table->foreignIdFor( \App\Models\Chapter::class );
            $table->foreignIdFor( \App\Models\YCPContact::class );
            $table->boolean( 'home' )->default( false );
            $table->timestamps();
        } );
        //Many to Many relation table
        Schema::create( 'y_c_p_company_y_c_p_contact', function ( Blueprint $table ) {
            $table->id();
            $table->foreignIdFor( \App\Models\YCPContact::class );
            $table->foreignIdFor( \App\Models\YCPCompany::class );
            $table->boolean( 'billing' );
            $table->boolean( 'contact' );
            $table->timestamps();
        } );
        Schema::create( 'addresses', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'street1' );
            $table->string( 'street2' )->nullable();
            $table->string( 'city' )->nullable();
            $table->string( 'state' )->nullable();
            $table->string( 'postal_code' )->nullable();
            $table->string( 'country' );
            $table->integer( 'addressable_id' ); //one to one polymorphic companies and contacts
            $table->string( 'addressable_type' ); //one to one polymorphic companies and contacts
            $table->timestamps();
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
    }
};
