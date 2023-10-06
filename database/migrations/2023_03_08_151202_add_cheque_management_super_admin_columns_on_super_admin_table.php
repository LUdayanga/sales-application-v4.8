<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddChequeManagementSuperAdminColumnsOnSuperAdminTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('super_admins', function (Blueprint $table) {
            $table->tinyInteger('cheque_payment_set_as_paid')->default(1)->after('enable_short_cut_purchase');
            $table->tinyInteger('increase_chque_due_in_reports')->default(0)->after('cheque_payment_set_as_paid');
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
