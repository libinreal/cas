<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCasServiceUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cas_service_users', function (Blueprint $table) {
            $table->integer('service_id')->unsigned()->comment('cas_services的外键');
            $table->string('user_name', 60)->default('')->comment('service对应的用户名');
            $table->char('token', 60)->default('')->comment('不同service帐号关联的token');
            $table->timestamps();
            $table->foreign('token')->references('token')->on('cas_users')->onDelete('cascade')->onUpdate('cascade');
            $table->primary(['service_id', 'user_name']);
            $table->index('token');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('cas_service_users');
    }
}
