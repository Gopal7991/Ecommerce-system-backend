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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('coupon_type');
            $table->foreignId('brand_id')->nullable()->constrained('brands')->onDelete('set null');
            $table->decimal('discount_percentage', 10, 2)->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('min_order_amount', 10, 2);
            $table->decimal('max_discount', 10, 2);
            $table->integer('max_attach')->default(1); 
            $table->integer('used_count')->nullable()->default(0); 
            $table->boolean('is_active')->default(true);
            $table->timestamps();
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
        Schema::dropIfExists('coupons');
    }
};
