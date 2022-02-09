<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanEmiHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loan_emi_histories', function (Blueprint $table) {
            $table->id();
			$table->bigInteger('loan_id')->unsigned()->index();
			$table->bigInteger('user_id')->unsigned()->index();
			$table->double("emiAmount",5,2);
			$table->date("emiDate");
			$table->date("emiPayDate")->nullable();
			$table->enum('status',['Paid','Pending'])->default('Pending');
			$table->foreign('loan_id')->references('id')->on('loan_requests')->onDelete('cascade');
			$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('loan_emi_histories');
    }
}
