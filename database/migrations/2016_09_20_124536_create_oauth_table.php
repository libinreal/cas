<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOauthTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_oauth', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->primary();
            //Add foreign key later as the coustom table is possibly not yet created by stephen 2018/03/08
            // $table->foreign('user_id')->references(config('cas.user_table.id'))->on(config('cas.user_table.name'))->onDelete('cascade');
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
        Schema::dropIfExists('user_oauth');
    }
}
