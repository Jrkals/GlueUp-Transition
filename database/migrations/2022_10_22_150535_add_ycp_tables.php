<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ycp_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('nb_tags')->nullable();
            $table->timestamps();
        });
        Schema::create('ycp_companies', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('chapter_ycp_contact', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Chapter::class);
            $table->foreignIdFor(\App\Models\YCPContact::class);
            $table->boolean('home')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ycp_contacts');
        Schema::dropIfExists('ycp_companies');
    }
};
