<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanRequest extends Model
{
     protected $table="loan_requests";
	 
    protected $fillable = [
        'user_id', 'amount','terms','rate','status','ApproveDate','emi','numPayEmi','NextEmiDate'
    ];
}
