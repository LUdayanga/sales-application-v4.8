<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEnableIncreaseDecreaseStockAdjustmentColumnOnSuperAdminTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('super_admins', function (Blueprint $table) {
            $table->integer('advance_stock_adjustment')->default(0)->after('bulk_payment');
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
