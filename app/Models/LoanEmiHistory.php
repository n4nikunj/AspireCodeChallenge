<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanEmiHistory extends Model
{
    protected $table="loan_emi_histories";
	 
    protected $fillable = [
        'user_id','loan_id', 'emiAmount','emiDate','emiPayDate','status'
    ];
}
