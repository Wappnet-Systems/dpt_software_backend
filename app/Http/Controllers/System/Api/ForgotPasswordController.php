<?php

namespace App\Http\Controllers\System\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function sendResetLinkEmail(Request $request)
    {
        $validation = [
            'email' => 'required',
        ];

        $rules = [
            'email.required' => 'Enter the email.',
        ];

        $validator = Validator::make($request->all(), $validation, $rules);

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                return $this->sendError('Validation Error.', [$key => $value[0]]);
            }
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            return $this->sendResponse([], 'We have emailed your password reset link!');
        } else if ($status == Password::INVALID_USER) {
            return $this->sendError('We can not find a user with that email address.');
        } else if ($status == Password::INVALID_TOKEN) {
            return $this->sendError('Invalid Token.');
        } else if ($status == Password::RESET_THROTTLED) {
            return $this->sendError('You have requested password reset recently, please check your email or you can request after 60 seconds.');
        }

        return $this->sendError('Something want wrong.');
    }
}
