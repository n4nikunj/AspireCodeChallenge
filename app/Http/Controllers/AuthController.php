<?php

namespace App\Http\Controllers;

use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use DB;
class AuthController extends Controller
{
    public function login(Request $request)
	{
		 $email = $request->email;
		$password = $request->password;
		
		if(empty($email) or empty($password))
		{
			 return response()->json([
				"success"=> "0",
				"status"=> "400",
				'message' =>"You must fill all fields",
				"data"=>(object) array()
			], 200);
		}
		

		$client = new Client();
		try{
			
			$key =  DB::table('oauth_clients')->select('secret', 'id')->where('name','Lumen Password Grant Client')->first();
			
			return $client->post(config('service.passport.login_endpoint'),[
				  "form_params" => [
                    "client_secret" =>$key->secret,
                    "grant_type" => "password",
                    "client_id" => $key->id,
                    "username" => $request->email,
                    "password" => $request->password
                ]
			]);
		}catch(BedResponseException $e){
			return response()->json([
				"success"=> "0",
				"status"=> "400",
				'message' =>$e->getMessage(),
				"data"=>(object) array()
			], 200);
		}
	}
	
	public function register(Request $request)
    {
        $name = $request->name;
        $email = $request->email;
        $password = $request->password;

        // Check if field is not empty
        if (empty($name) or empty($email) or empty($password)) {
			return response()->json([
				 "success"=> "0",
				"status"=> "400",
                'message' => 'You must fill all the fields',
				"data"=>array()
            ], 200);
        }

        // Check if email is valid
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return response()->json([
				 "success"=> "0",
				"status"=> "400",
                'message' => 'You must enter a valid email',
				"data"=>array()
            ], 200);
        }


        // Check if user already exist
        if (User::where('email', '=', $email)->exists()) {
           
			return response()->json([
				 "success"=> "0",
				"status"=> "400",
                'message' => 'User already exists with this email',
				"data"=>array()
            ], 200);
        }

        // Create new user
        try {
            $user = new User();
            $user->name = $name;
            $user->email = $email;
            $user->password = app('hash')->make($password);
			
            if ($user->save()) {
                // Will call login method
				$us = User::find($user->id);
				$us->assignRole('User');
				return response()->json([
				"success"=> "1",
				"status"=> "200",
				'message' =>"User Create Successfuly",
				"data"=>$user
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

    public function logout(Request $request)
    {
        try {
            auth()->user()->tokens()->each(function ($token) {
                $token->delete();
            });
			return response()->json([
				"success"=> "1",
				"status"=> "200",
				'message' =>"Logged out successfully",
				"data"=>$user
				], 200);
            
        } catch (\Exception $e) {
            return response()->json([
				"success"=> "0",
				"status"=> "400",
				'message' =>$e->getMessage(),
				"data"=>(object) array()
			], 200);
        }
    }
  
}
