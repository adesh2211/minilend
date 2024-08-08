<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CustomerApplications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ssn');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('email')->nullable();
            $table->string('title')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->date('dob')->nullable();
            $table->string('profile_image')->nullable();
            $table->tinyInteger('enable')->default(1);
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
        });

        Schema::create('customer_applications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('application_number');
            $table->json('customer_info')->nullable();
            $table->string('status');
            $table->unsignedBigInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->timestamps();
        });

        Schema::create('customer_application_infos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->year('year');
            $table->json('current_assets')->nullable();
            $table->json('fixed_assets')->nullable();
            $table->json('other_assets')->nullable();
            $table->double('total_assets')->nullable();
            $table->json('current_liabities')->nullable();
            $table->json('long_term_liabities')->nullable();
            $table->json('owner_equity')->nullable();
            $table->double('total_liabities')->nullable();
            $table->json('common_financial_ratios')->nullable();
            $table->json('revenue')->nullable();
            $table->json('goods_sold')->nullable();
            $table->json('expenses')->nullable();
            $table->double('income_from_con_ope')->nullable();
            $table->json('below_line_items')->nullable();
            $table->double('net_income')->nullable();
            $table->unsignedBigInteger('customer_application_id')->nullable();
            $table->foreign('customer_application_id')->references('id')->on('customer_applications');
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
        Schema::dropIfExists('customers');
        Schema::dropIfExists('customer_applications');
        Schema::dropIfExists('customer_application_infos');
    }
}
