<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCustomerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('middle_name')->nullable();
            $table->string('gender')->nullable();
            $table->text('home_address')->nullable();
            $table->string('country_code')->nullable();
            $table->unsignedBigInteger('phone_number')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('zip_code')->nullable();
        });

        Schema::table('customer_applications', function (Blueprint $table) {
            $table->string('email')->nullable();
            $table->date('dob')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('gender')->nullable();
            $table->string('title')->nullable();
            $table->text('home_address')->nullable();
            $table->string('country_code')->nullable();
            $table->unsignedBigInteger('phone_number')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('zip_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('middle_name');
            $table->dropColumn('gender');
            $table->dropColumn('home_address');
            $table->dropColumn('country_code');
            $table->dropColumn('phone_number');
            $table->dropColumn('city');
            $table->dropColumn('state');
            $table->dropColumn('country');
            $table->dropColumn('zip_code');
        }); 

        Schema::table('customer_applications', function (Blueprint $table) {
            $table->dropColumn('email');
            $table->dropColumn('first_name');
            $table->dropColumn('last_name');
            $table->dropColumn('middle_name');
            $table->dropColumn('gender');
            $table->dropColumn('home_address');
            $table->dropColumn('country_code');
            $table->dropColumn('phone_number');
            $table->dropColumn('city');
            $table->dropColumn('state');
            $table->dropColumn('country');
            $table->dropColumn('zip_code');
        });
    }
}
