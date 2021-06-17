<?php

    namespace App\Http\Controllers;
    use App\User;
    use App\NotificationSetting;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Support\Facades\Validator;
    use JWTAuth; 
    use Auth;
    use App\Mail\PasswordReset;
    use App\Mail\EmailVerification;
    use Mail;
    use Tymon\JWTAuth\Exceptions\JWTException;
    use Illuminate\Support\Facades\Session;

    // use Bugsnag\BugsnagLaravel\Facades\Bugsnag;

    class UserController extends Controller
    {
        public function getUser(Request $request){
            return "hello";
        }
        public function changePassword(Request $request){
            $user = User::where('id', $request->get('user_id'))->first();
            $current_password = $request->input('current_password');
            $new_password = $request->input('new_password');

            if (Hash::check($current_password, $user['password'])) {
                $password = Hash::make($new_password);
                $result = User::where('id', $request->get('user_id'))->update([
                    'password'=>$password
                ]);
                if($result == 1){
                    return response()->json([
                        "success"=>true
                    ],200);
                }else{
                    return response()->json([
                        "success"=>false
                    ],200);
                }
            }
            else
            {
                return response()->json([
                    "msg" => 'Password does not matchs',
                    "success"=>false
                ],200);        
            }
        }
        public function forgotPassword(Request $request){
            $email = $request->email;
                if (!$email) {
                    return response()->json([
                        'message'=> 'Invalid Request!'
                    ],401);
                }
                $check = User::where('email',$email)->count();
                if ($check == 0) {
                    return response()->json([
                        'message'=> 'There is no account with this email!'
                    ],401);
                }
                if($check){
                    $token =  rand(100000, 999999);
                } 
                User::where('email', $email)->update(['password_token'=> $token]);
                $user = User::where('email', $email)->first();
                 
                $email_data = [
                    'code' => $token 
                ];
                try{
                    $mail = Mail::to($email)->send(new PasswordReset($email_data));
                }catch (\Exception $e) {
                    return e.response();
                }
                $mail = Mail::to($email)->send(new PasswordReset($email_data));
                return response()->json([
                    'user'=> $user,
                    'token'=> $token
                ],200);
        }
        public function verifyPasswordResetCode( Request $request) {
            $datatoken = $request->token;
            if (!$datatoken) {
                return response()->json([
                    'message'=> 'Invalid Request!'
                ],401);
            }
            $check = User::where('password_token',$datatoken)->count();
            if ($check == 1) {
                return 'Token verified!';
            }else{
                return response()->json([
                    'message'=> 'Invalid Code'
                ],401);
             }
        }
        public function resetPassword( Request $request) {
                $validator = Validator::make($request->all(), [
                    'newPassword' => 'required|string|min:6',
                ]);
                if($validator->fails()){
                    return response()->json($validator->errors(), 400);
                }
                // $user = JWTAuth::parseToken()->authenticate();
                $user = User::where('email',$request->get('email'))->first();
                $user_id = $user->id;
                $password = Hash::make($request->get('newPassword'));
                $password_set = User::where('id',$user_id)->where('password_token', $request->token)->update(['password'=> $password,'password_token'=> NULL]);
                if($password_set){
                    return response()->json([
                        'success'=> 'true'
                    ],200);
                }else{
                    return response()->json([
                        'msg' => "Invalid Credential",
                        'success'=> 'false'
                    ],400);
                }
               
            }
        public function authenticate(Request $request){
            if (Auth::attempt(['email' => $request->email, 'password' => $request->passwordm])) {
                Auth::user();
            }
            $input = $request->only('email', 'password');
            $jwt_token = null;
            if (!$jwt_token = JWTAuth::attempt($input)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Email or Password',
                ], 401);
            }else{
                if(Auth::user()->email_verified_at != null){
                    $user_id = Auth::user()->id;
                    $update = User::where('id', $user_id)->update([
                        'device_id' => $request->get('device_id'),
                    ]);
                    $user = User::where('id', $user_id)->first();
                    return response()->json([
                        'success' => true,
                        'token' => $jwt_token,
                        'user' => $user
                    ]);
                }else{
                    return response()->json([
                        'success' => false,
                        'message' => 'Please verify your email.',
                    ],400);
                }
                
            }
        }
        public function verifyCode( Request $request){
            $datatoken = $request->token;
            if (!$datatoken) {
                return response()->json([
                    'message'=> 'Invalid Request!'
                ],401);
            }
            $today_date = date("Y/m/d");
            $check = User::where('email_verification_token',$datatoken)->update([
                "email_verified_at" =>  $today_date
            ]);
            if ($check == 1) {
                return response()->json([
                    'message'=> 'Token verified!'
                ],200);
            }else{
                return response()->json([
                    'message'=> 'Invalid Code'
                ],401);
            }
        }
        public function register(Request $request){
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
            ]);
            if($validator->fails()){
                return response()->json($validator->errors(), 400);
            }
            $token =  rand(100000, 999999);
            $user = User::create([
                'first_name' => $request->get('first_name'),
                'last_name' => $request->get('last_name'),
                'optional_email' => $request->get('optional_email'),
                'city' => $request->get('city'),
                'state' => $request->get('state'),
                'email' => $request->get('email'),
                'organization' => $request->get('organization'),
                'mobile' => $request->get('mobile'),
                'home_phone' => $request->get('home_phone'),
                'office_phone' => $request->get('office_phone'),
                'password' => Hash::make($request->get('password')),
                'profile_picture' => $request->get('profile_picture'),
                'user_type' => $request->get('user_type'),
                'device_id' => $request->get('device_id'),
                'email_verification_token' => $token,
            ]);
            $notification= NotificationSetting::create([
                'user_id' => $user->id
            ]);
            if($user){
                $email_data = [
                    'token' => $token 
                ]; 
                try{
                    $mail = Mail::to($user->email)->send(new EmailVerification($email_data));
                }catch (\Exception $e) {
                    return e.response();
                }
                return response()->json([
                    'success' => true,
                    'msg' => "Verification code has sent on your email. Please verify ths to login.",
                ],200);
            } 
            else{
                return response()->json([
                    'success' => false,
                    'msg' => " Something went wrong.",
                ],400);
            }
            
            // if($user){
            //     if (Auth::attempt(['email' => $request->email, 'password' => $request->password ])) {
            //         Auth::user();
            //     }
            //     $input = $request->only('email', 'password');
            //     $jwt_token = null;
            //     if (!$jwt_token = JWTAuth::attempt($input)) {
            //         return response()->json([
            //             'success' => false,
            //             'message' => 'Invalid Email or Password',
            //         ], 401);
            //     }
            //     $user = Auth::user();
            //     return response()->json([
            //         'success' => true,
            //         'token' => $jwt_token,
            //         'user' => $user
            //     ]);
            // }
        }
        public function getAuthenticatedUser()
            {
                    try {
                            if (! $user = JWTAuth::parseToken()->authenticate()) {
                                    return response()->json(['user_not_found'], 404);
                            }
                    } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
                            return response()->json(['token_expired'], $e->getStatusCode());
                    } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

                            return response()->json(['token_invalid'], $e->getStatusCode());

                    } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {

                            return response()->json(['token_absent'], $e->getStatusCode());

                    }

                    return response()->json(compact('user'));
        }
        public function logout(){
            $user = JWTAuth::parseToken()->authenticate();
            $user_id = $user->id;
            $update = User::where('id', $user_id)->update(
                [
                    'device_id' => null
                ]);
            Auth::logout();
            Session::flush();
            return response()->json([
                'success' => true,
            ]);
        }
    }    