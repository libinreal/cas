<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateServicesTable extends Migration
{
    /**
     * Run the migrations.
     * cas service 表
     * @return void
     */
    public function up()
    {
        Schema::create('cas_services', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->charset('utf8')->collate('utf8_general_ci')->unique();
            $table->boolean('enabled')->default(true);
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
        Schema::drop('cas_services');
    }
}
