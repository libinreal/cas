<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeyUserIdToProxyGrantingTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cas_proxy_granting_tickets', function (Blueprint $table) {
            $table->foreign('user_id')->references(config('cas.user_table.id'))->on(config('cas.user_table.name'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cas_proxy_granting_tickets', function (Blueprint $table) {
            $table->dropForeign('user_id');
        });
    }
}
