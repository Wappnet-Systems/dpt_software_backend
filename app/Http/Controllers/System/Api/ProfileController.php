<?php

namespace App\Http\Controllers\System\Api;

use App\Http\Controllers\Controller;
use App\Models\System\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\System\UserLoginLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function logout(Request $request)
    {
        $user = $request->user();

        /* if (!empty($request->device_type) || !empty($request->device_id)) {
            $device = UserLoginLog::where('user_id', $user->id);

            if ($request->device_type) $device->where('device_type', $request->device_type);
            if ($request->device_id) $device->where('device_id', $request->device_id);

            $deviceData = $device->first();

            if ($deviceData) {
                $deviceData->delete();
            }
        } */

        $request->user()->currentAccessToken()->delete();

        return $this->sendResponse([], 'Logged out successfully.');
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $user = User::find($user->id);

        $validator = Validator::make($request->all(), [
            'old_password' => [ function($attribute, $value, $fail) {
                if (!Hash::check($value, auth()->user()->password)) {
                    $fail('The :attribute is incorrect.');
                }
            }],
            'new_password' => 'required'
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                return $this->sendError('Validation Error.', [$key => $value[0]]);
            }
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return $this->sendResponse($user, 'Password updated successfully.');
    }
}
