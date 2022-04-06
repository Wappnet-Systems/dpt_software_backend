<?php

namespace App\Http\Controllers\System\Api;

use App\Http\Controllers\Controller;
use App\Models\System\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\System\Role;
use App\Models\System\User;
use App\Models\System\UserLoginLog;

class UserController extends Controller
{
    public function login(Request $request)
    {
        $user = User::where('email', '=', strtolower($request->email))->first();

        if (isset($user) && !empty($user)) {
            if (!Role::whereId($user->role_id)->exists()) {
                return $this->sendError('User does not exist.');
            } /* else if ($user->type != User::USER_ROLE['SUPER_ADMIN'] && empty($user->is_email_verified)) {
                return $this->sendError('Please verify your email by clicking the link sent to your email address.');
            } */ else if ($user->type != User::USER_ROLE['SUPER_ADMIN'] && $user->status != User::STATUS['Active']) {
                return $this->sendError('This user has been inactivated by admin. Please contact to admin.');
            } else {
                if (Auth::attempt(['email' => strtolower($request->email), 'password' => $request->password])) { 
                    $user = Auth::user();
                    
                    $user->token = $user->createToken(env('APP_NAME'))->plainTextToken;

                    if (isset($request->device_id) && !empty($request->device_id)) {
                        $req['deviceType']  = !empty($request->device_type) ? $request->device_type : '';
                        $req['deviceId'] = $request->device_id;

                        $this->deviceLogin($req, $user->toArray());
                    }

                    $data = [
                        'user' => $user,
                        'modules' => Module::pluck('name', 'id'),
                        'assign_modules' => Module::isAssigned($user->organization_id ?? null)->get()->toArray()
                    ];
        
                    return $this->sendResponse($data, 'User login successfully.');
                } else {
                    return $this->sendError('Invalid credentials.');
                }
            }
        } else {
            return $this->sendError('User does not exist.');
        }
    }

    private function deviceLogin($req = array(), $userData = null)
    {
        $deviceType = (isset($req['deviceType']) && $req['deviceType']) ? $req['deviceType'] : "";
        $deviceId   = (isset($req['deviceId']) && $req['deviceId']) ? $req['deviceId'] : "";

        if (!empty($deviceType)) {
            $device = UserLoginLog::where('user_id', '=', $userData['id'])
                ->where('device_type', $deviceType)
                ->where('device_id', $deviceId)
                ->first();

            if (!empty($device)) {
                $device->update([
                    'device_type'   => $deviceType,
                    'device_id'     => $deviceId
                ]);
            } else {
                $deviceData = [
                    'user_id'       => $userData['id'],
                    'device_type'   => $deviceType,
                    'device_id'     => $deviceId,
                ];

                UserLoginLog::insert($deviceData);
            }
        }
    }
}
