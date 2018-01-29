<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCasUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cas_users', function (Blueprint $table) {
            $table->char('token', 60)->default('')->comment('不同service帐号关联的token');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->primary('token');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {   
        //drop foreign key table cas_service_users
        Schema::drop('cas_service_users');
        Schema::drop('cas_users');
    }
}
