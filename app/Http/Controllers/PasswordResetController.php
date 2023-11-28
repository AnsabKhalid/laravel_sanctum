<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Mail\Message;
use Carbon\Carbon;

use App\Models\PasswordReset;
use App\Models\User;

class PasswordResetController extends Controller
{
    public function send_reset_password_email(Request $request) {
        $request->validate([
            'email' => 'required|email'
        ]);
        $email = $request->email;

        // Check user's email exists
        $user = User::where('email', $email)->first();
        if(!$user) {
            return response([
                'message' => "Email does not exist",
                'status' => 'failed'
            ], 404);
        }

        // Generate Token
        $token = Str::random(60);

        // Saving data to Password Reset Table
        PasswordReset::create([

            'email' => $email,
            'token' => $token,
            'created_at' => Carbon::now(),
        ]);

        dump("http://127.0.0.1:3000/api/reset/" . $token);
        dd("http://127.0.0.1:3000/api/reset/" . $token);

        //Sending Email with Password reset view
        // Mail::send('reset', ['token' => $token], function(Message $message)use($email) {
        //     $message->subject('Reset Your Password');
        //     $message->to($email);
        // });

        return response([
            'message' => "Password reset email sent...Check your Email",
            'status' => 'success'
        ], 200);
    }

    public function reset(Request $request, $token){
        // Delete Token older than 2 minute
        $formatted = Carbon::now()->subMinutes(2)->toDateTimeString();
        PasswordReset::where('created_at', '<=', $formatted)->delete();

        $password = $request->validate([
            'password' => 'required|string|confirmed',
        ]);

        $passwordReset = PasswordReset::where('token', $token)->first();

        if(!$passwordReset) {
            return response([
                'message' => "Token is Invalid or Expired",
                'status' => 'failed'
            ], 404);
        }

        $user = User::where('email', $passwordReset->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete the token after reseting the password
        PasswordReset::where('email', $user->email)->delete();

        return response([
            'message' => "Password reset Successfully",
            'status' => 'success'
        ], 200);
    }
}
