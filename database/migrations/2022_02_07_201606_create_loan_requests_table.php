<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loan_requests', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned()->index();
            $table->double('amount', 8, 2);
            $table->integer("terms");
            $table->double('rate', 5, 2);
			$table->double("emi",5,2)->nullable();
			$table->integer("numPayEmi")->nullable();
			$table->date("ApproveDate")->nullable();
			$table->date("NextEmiDate")->nullable();
            $table->enum('status',['Approve','Reject','Pending'])->default('Pending');
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
        Schema::dropIfExists('loan_requests');
    }
}
