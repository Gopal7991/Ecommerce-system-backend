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
        Schema::table('users', function (Blueprint $table) {
            $table->string('lastname')->nullable()->after('name');
            $table->string('gender')->nullable()->after('lastname');
            $table->date('birthdate')->nullable()->after('gender');
            $table->string('profile_image')->nullable()->after('birthdate');
            $table->string('mobile')->nullable()->after('email');

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['lastname', 'gender', 'birthdate', 'profile_image','mobile']);
        });
    }
};
