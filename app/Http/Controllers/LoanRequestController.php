<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\LoanRequest;
use App\Models\LoanEmiHistory;
use URL;
class LoanRequestController extends Controller
{
    public function loanRequest(Request $request)
	{
		$loanAmount = $request->loanAmount;
		$term = $request->term;
		$user = $request->user();
		if (empty($loanAmount) ) {
			return response()->json([
				 "success"=> "0",
				"status"=> "400",
                'message' => 'Loan Amount is a required field',
				"data"=>(object)array()
            ], 200);
        }
		
		if (empty($term) ) {
			return response()->json([
				 "success"=> "0",
				"status"=> "400",
                'message' => 'Term is a required field',
				"data"=>(object)array()
            ], 200);
        }
		 try {
            $loan = new LoanRequest();
            $loan->user_id = $user->id;
            $loan->amount = $loanAmount;
            $loan->rate = config('service.loan.rate');
            $loan->terms = $term;
            $loan->status = "Pending";

            if ($loan->save()) {
                // Will call login method
				$result = array(
					"userName"=>$user->name,
					"amount"=>$loanAmount,
					"IntrestRate"=>$loan->rate,
					"terms"=>$loan->terms." Week",
					"status"=>$loan->status
				);
				return response()->json([
				"success"=> "1",
				"status"=> "200",
				'message' =>"Loan Request Send Successfully",
				"data"=>$result
				], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
				"success"=> "0",
				"status"=> "400",
				'message' =>$e->getMessage(),
				"data"=>(object) array()
			], 200);
        }

	}
	public function loanRequestList(Request $request)
	{
		$perPage = config('service.loan.perPagePagination');
		$user = $request->user();
		
		if($user->hasRole('User'))
		{
			$loanreq = LoanRequest::where('user_id',$user->id)->paginate($perPage);
		}else{
			if($request->status != "")
			{
				$loanreq = LoanRequest::where('status',$request->status)->paginate($perPage);
			}else{
			$loanreq = LoanRequest::paginate($perPage);	
			}
			
		}
		
		
		$postdata = $request->all();
		 $TotalPage = $loanreq->lastPage();
		 $curpage = (isset($postdata['page']))?$postdata['page']:1;
		if (count($loanreq)==0) {
			return response()->json([
				"success"=> "0",
				"status"=> "200",
				'message' => "No Loan Request found",
				"data"=>(object)array()
			], 200);
		}
		
		$list = array();
		
		
		foreach($loanreq as $val)
		{
			$u=User::find($val->user_id);
			$res['requestid'] = (string)$val->id;
			$res['name'] = $u->name;
			$res['amount'] = (string)$val->amount;
			$res['terms'] = (string)$val->terms;
			$res['rate'] = (string)$val->rate;
			$res['status'] = $val->status;
			$principal = $val->amount;
			$rate = $val->rate;
			$time = $val->terms;
			$emi = $this->emi_calculator($principal, $rate, $time);
			$res['weeklyEmi'] =number_format((float)$emi, 2, '.', '');
			$list[] = $res;
		}
		$nexpageNo = $curpage+1;
		$prevpageno = $curpage - 1;
		if($prevpageno == 0)
		{
			$prevpageno = 1;
		}
		$next = $prev = "";
		if($TotalPage == $curpage)
		{
			
			$prev = URL::current()."?page=".$prevpageno;
		}else{
			
			$next = URL::current()."?page=".$nexpageNo;
			$prev = URL::current()."?page=".$prevpageno;
		}
	
		if($TotalPage == 1)
		{
			$prev ="";
			$next ="";
		}   
		
		
		 return response()->json([
			"success"=> "1",
			"status"=> "200",
			"message"=> "Loan request list got successfully",
			"data"=>$list,
			 "links"=>[
							"first"=> URL::current()."?page=1",
							"last"=> URL::current()."?page=".$TotalPage,
							"prev"=> $prev,
							"next"=> $next
						],
			"meta"=> [
				"current_page"=> $curpage,
				"last_page"=> $TotalPage,
				"per_page"=> $perPage,
				"total"=> $TotalPage
			]
			]);
	}
	function emi_calculator($p, $r, $t)
	{
		
		$r = $r / (52 * 100);
		$emi = ($p * $r * pow(1 + $r, $t)) / (pow(1 + $r, $t) - 1);
	 
		return $emi;
	}
	function loanAction(Request $request)
	{
		
		$user = $request->user();
		if($user->hasRole('Admin'))
		{
			$requestid = $request->requestid;
			$status = $request->status;
			if (empty($requestid) ) {
				return response()->json([
					 "success"=> "0",
					"status"=> "400",
					'message' => 'Loan requestid is a required field',
					"data"=>(object)array()
				], 200);
			}
			
			if (empty($status) ) {
				return response()->json([
					 "success"=> "0",
					"status"=> "400",
					'message' => 'status is a required field',
					"data"=>(object)array()
				], 200);
			}
			try{
				
				
				$loanreq = LoanRequest::find($request->requestid);
				if($loanreq){
					if($loanreq->status != "Approve")
					{
						$emi =$this->emi_calculator($loanreq->amount, $loanreq->rate, $loanreq->terms);
						$upd['status'] = $status;
						$upd['emi'] = $emi;
						$upd['numPayEmi'] = 0;
						$upd['ApproveDate'] = date('Y-m-d');
						$upd['NextEmiDate'] = date("Y-m-d", strtotime("+1 week"));
						
						$upddta = LoanRequest::where('id',$request->requestid)->update($upd);
						
						$ins['loan_id'] = $loanreq->id;
						$ins['user_id'] = $loanreq->user_id;
						$ins['emiAmount'] = $emi;
						$ins['emiDate'] = date("Y-m-d", strtotime("+1 week"));
						$ins['status'] = "Pending";
						LoanEmiHistory::create($ins);
						
						return response()->json([
							 "success"=> "1",
							"status"=> "200",
							'message' => 'Loan Request approved successfully',
							"data"=>(object)array()
						], 200);
						
						
					}else{
						return response()->json([
							 "success"=> "1",
							"status"=> "200",
							'message' => 'Loan Request already approved',
							"data"=>(object)array()
						], 200);
					}
				
					
				}else{
					return response()->json([
						 "success"=> "0",
						"status"=> "400",
						'message' => 'Please pass valid loan request id',
						"data"=>(object)array()
					], 200);
				}
				
				
				
				
			} catch (\Exception $e) {
				return response()->json([
					"success"=> "0",
					"status"=> "400",
					'message' =>$e->getMessage(),
					"data"=>(object) array()
				], 200);
			}
			
		}else{
			return response()->json([
				"success"=> "0",
				"status"=> "400",
				'message' =>"Sorry, You dont have permission to access",
				"data"=>(object) array()
			], 200);
		}
		
	}
	function loanDetail(Request $request)
	{
		$requestid = $request->requestid;
		
		if (empty($requestid) ) {
			return response()->json([
				 "success"=> "0",
				"status"=> "400",
				'message' => 'Loan requestid is a required field',
				"data"=>(object)array()
			], 200);
		}
		try{
			$user = $request->user();
			if($user->hasRole('Admin'))
			{
				$loanreq = LoanRequest::find($request->requestid);
			}else
			{
				$loanreq = LoanRequest::where(['id'=>$request->requestid,'user_id'=>$user->id])->first();
			}
			
				if($loanreq){
					
					$response['requestId'] = (string)$loanreq->id;
					$response['userName'] = User::get_user($loanreq->user_id);
					$response['amount'] = (string)$loanreq->amount;
					$response['terms'] = (string)$loanreq->terms;
					$response['rate'] = (string)$loanreq->rate;
					$response['ApproveDate'] = $loanreq->ApproveDate;
					$response['status'] = $loanreq->status;
					
					$loanhis = LoanEmiHistory::where("loan_id",$loanreq->id)->get();
					$i =1;
					$result = array();
					
					foreach ($loanhis as $val)
					{
						$res['sr']= (string)$i;
						$res['emiId']= (string)$val->id;
						$res['emiAmount']= (string)$val->emiAmount;
						$res['emiDate']= $val->emiDate;
						$res['emiPayDate']= ($val->emiPayDate)?$val->emiPayDate:"";
						$res['status']= $val->status;
						$result[] = $res; 
						$i++;
					}
					$response['emiDetail'] = $result;
					return response()->json([
						 "success"=> "1",
						"status"=> "200",
						'message' => 'data got successfully',
						"data"=>$response
					], 200);
				}else{
					return response()->json([
						 "success"=> "0",
						"status"=> "400",
						'message' => 'Please pass valid loan request id',
						"data"=>(object)array()
					], 200);
				}
				
		} catch (\Exception $e) {
			return response()->json([
				"success"=> "0",
				"status"=> "400",
				'message' =>$e->getMessage(),
				"data"=>(object) array()
			], 200);
		}
			
		
	}
	function repayment(Request $request)
	{
		$user = $request->user();
		if($user->hasRole('User'))
		{
			
			$amount = $request->emiAmount;
			$emiId = $request->emiId;
			
			if (empty($amount) ) {
				return response()->json([
					 "success"=> "0",
					"status"=> "400",
					'message' => 'Loan EMI Amount is a required field',
					"data"=>(object)array()
				], 200);
			}
			if (empty($emiId) ) {
				return response()->json([
					 "success"=> "0",
					"status"=> "400",
					'message' => 'EMI id is a required field',
					"data"=>(object)array()
				], 200);
			}
			try{
				$emihistory  = LoanEmiHistory::where(['id'=>$emiId,'user_id'=>$user->id])->first();
				if($emihistory)
				{	
					if($emihistory->emiAmount == $amount)
					{
						$upd["emiPayDate"] =  date("Y-m-d");
						$upd["status"] =  "Paid";
						LoanEmiHistory::where("id",$emiId)->update($upd);
						$LoanRequest = LoanRequest::find($emihistory->loan_id);
						if($LoanRequest->numPayEmi+1 < $LoanRequest->terms )
						{
							$LoanRequest->numPayEmi += 1;
							$LoanRequest->NextEmiDate =date("Y-m-d", strtotime("+1 week"));
							$LoanRequest->save();
							
							$ins['loan_id'] = $LoanRequest->id;
							$ins['user_id'] = $LoanRequest->user_id;
							$ins['emiAmount'] = $LoanRequest->emi;
							$ins['emiDate'] = date("Y-m-d", strtotime("+1 week"));
							$ins['status'] = "Pending";
							LoanEmiHistory::create($ins);
							
							return response()->json([
								"success"=> "1",
								"status"=> "200",
								'message' =>"Thank for Payment yoru next EMI ".$LoanRequest->emi ." on ".date("Y-m-d", strtotime("+1 week")),
								"data"=>(object) array()
							], 200);
							
						}else{
							$LoanRequest->numPayEmi += 1;
							$LoanRequest->NextEmiDate ="";
							$LoanRequest->status ="Approve";
							$LoanRequest->save();
							return response()->json([
								"success"=> "1",
								"status"=> "200",
								'message' =>"Thank for Payment! You have Successfully pay all EMI",
								"data"=>(object) array()
							], 200);
						}
						
					}else{
						return response()->json([
						"success"=> "0",
						"status"=> "400",
						'message' =>"EMI ammount is ".$emihistory->emiAmount ." Please pass proper amount",
						"data"=>(object) array()
					], 200);
					}
				}else{
					return response()->json([
						 "success"=> "0",
						"status"=> "400",
						'message' => 'EMI detail not found',
						"data"=>(object)array()
					], 200);
				}	
			} catch (\Exception $e) {
				return response()->json([
					"success"=> "0",
					"status"=> "400",
					'message' =>$e->getMessage(),
					"data"=>(object) array()
				], 200);
		}
				
		}else{
			return response()->json([
				"success"=> "0",
				"status"=> "400",
				'message' =>"Sorry, Admin dont have access to pay the payment",
				"data"=>(object) array()
			], 200);
		}	
	}
}
