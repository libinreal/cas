<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCasServiceUsersTable extends Migration
{
    /**
     * Run the migrations. 
     * 
     * Service 用户帐号表
     * @return void
     */
    public function up()
    {
        Schema::create('cas_service_users', function (Blueprint $table) {
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->integer('service_id')->unsigned()->comment('cas_services的外键');
            $table->integer('cas_user_id')->unsigned()->comment('cas_users的外键');
            $table->foreign('cas_user_id')->references('id')->on('cas_users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('service_id')->references('id')->on('cas_services')->onDelete('cascade')->onUpdate('cascade');
            $table->primary(['service_id', 'cas_user_id']);
            $table->string('user_name', 60)->default('')->comment('用户登录service时使用的帐号名');
            $table->string('user_id', 10)->default('')->comment('用户在service中的帐号id');
            $table->char('random_str', 60)->default('')->comment('用户登录service成功后的cas server 生成的随机字符串');
            $table->timestamp('updated_at')->nullable();
            $table->comment = '保存用户已登录过的cas services 的所有帐号，一个cas_user为一个用户，一个用户可有多个service帐号';
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
