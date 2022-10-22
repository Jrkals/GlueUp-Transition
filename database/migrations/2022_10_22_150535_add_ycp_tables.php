<?php

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
            $table->timestamps();
        } );
        Schema::create( 'y_c_p_companies', function ( Blueprint $table ) {
            $table->id();
            $table->timestamps();
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
