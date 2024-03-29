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
            'password' => 'required',
            'token' => 'required',
        ];

        $rules = [
            'password.required' => 'Enter the password.',
            'token.required' => 'Please send the token.',
        ];

        $validator = Validator::make($request->all(), $validation, $rules);

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                return $this->sendError('Validation Error.', [$key => $value[0]], 400);
            }
        }

        $tokens = explode(':', base64_decode($request->token));

        // Check if token are available
        if (!isset($tokens[0]) || !isset($tokens[1])) {
            return $this->sendError('Validation Error.', ['token' => 'Invalid token']);
        }

        $request->merge([
            'token' => $tokens[0],
            'email' => strtolower($tokens[1])
        ]);

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
        } else if ($status == Password::INVALID_TOKEN) {
            return $this->sendError('This password reset token is invalid');
        } else {
            return $this->sendError('We can not find a user with that email address.');
        }
    }
}
