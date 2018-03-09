<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * 原生 cas 后台管理员/普通用户 列表
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100)->collation('utf8mb4_unicode_ci')->unique();
            $table->string('real_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('enabled')->default(true);
            $table->boolean('admin')->default(false);
            $table->rememberToken();
            $table->timestamps();
            $table->comment = '原生cas用户表，包含管理员和普通帐号，本表只作管理员用，普通用户见 cas_users(用户主表),cas_service_users(用户关联service帐号表)';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users');
    }
}
