<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EnableWakingSupplierOnSuperAdminTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('super_admins', function (Blueprint $table) {
            $table->integer('enable_walking_suppler')->default(0)->after('enable_sale_bulk_return');
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
