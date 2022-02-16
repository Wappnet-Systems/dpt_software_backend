<?php

namespace App\Http\Controllers\System\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class ResetPasswordController extends Controller
{
    public function resetPassword(Request $request)
    {
        $validation = [
            'email' => 'required',
            'password' => 'required',
            'token' => 'required',
        ];

        $rules = [
            'email.required' => 'Enter the email.',
            'password.required' => 'Enter the password.',
            'token.required' => 'Please send the token.',
        ];

        $validator = Validator::make($request->all(), $validation, $rules);

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                return $this->sendError('Validation Error.', [$key => $value[0]]);
            }
        }

        $status = Password::reset(
            $request->only('email', 'password', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ]);
     
                $user->save();
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return $this->sendResponse([], 'Your password has been reset!');
        } else {
            return $this->sendError('We can not find a user with that email address.');
        }
    }
}
