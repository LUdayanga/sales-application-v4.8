<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBulkReturnQuantityOnStockAdjustmentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stock_adjustment_lines', function (Blueprint $table) {
            $table->decimal('bulk_return_quantity', 22, 4)->default(0)->after('lot_no_line_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stock_adjustment_lines', function (Blueprint $table) {
            //
        });
    }
}
