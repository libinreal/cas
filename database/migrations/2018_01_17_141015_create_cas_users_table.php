<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCasUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Service 用户表
     * @return void
     */
    public function up()
    {
        Schema::create('cas_users', function (Blueprint $table) {
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->increments('id');
            $table->char('token', 60)->default('')->comment('id和该token可作为保存用户登录状态的凭证');
            $table->boolean('enabled')->default(true);
            $table->string('email', 100)->default('')->comment('最近一次登录的service帐号的email');
            $table->string('real_name', 100)->default('')->comment('最近一次登录的service帐号的real_name');
            $table->string('name', 100)->default('')->comment('最近一次登录的service帐号的username');
            $table->integer('service_id')->unsigned()->default(0)->comment('最近一次登录的service的id, 对应cas_service表中的id');
            $table->timestamps();
            $table->comment = '保存已登录过的cas services 的所有用户';
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
