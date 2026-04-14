<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('coupons', function (Blueprint $table) {
            DB::table('coupons')->whereNull('used_count')->update(['used_count' => 0]);

            DB::statement("ALTER TABLE coupons MODIFY used_count INT NOT NULL DEFAULT 0");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('coupons', function (Blueprint $table) {
            DB::statement("ALTER TABLE coupons MODIFY used_count INT NULL DEFAULT NULL");
        });
    }
};
