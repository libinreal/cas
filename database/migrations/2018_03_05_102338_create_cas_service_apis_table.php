<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCasServiceApisTable extends Migration
{
    /**
     * Run the migrations. 
     * 
     * Service apis 配置表
     * @return void
     */
    public function up()
    {
        Schema::create('cas_service_apis', function (Blueprint $table) {
            $table->integer('service_id')->unsigned()->comment('cas_services 的外键');
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->foreign('service_id')->references('id')->on('cas_services')->onDelete('cascade')->onUpdate('cascade');
            $table->char('name',50)->default('')->comment('service api 名称');
            $table->string('url', 180)->default('')->comment('service api 地址，不包含host');
            $table->char('method', 8)->default('')->comment('service api request method');
            $table->string('fields', 30)->default('')->comment('service api request 字段');
            $table->string('response_fields', 10)->default('')->comment('service api response 的字段');
            $table->comment = 'cas service apis 配置表';
            $table->primary(['service_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('cas_service_apis');
    }
}
