<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EnableShortcutPurchaseOnSuperAdminTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('super_admins', function (Blueprint $table) {
            $table->integer('enable_short_cut_purchase')->default(0)->after('enable_walking_suppler');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('super_admins', function (Blueprint $table) {
            //
        });
    }
}
