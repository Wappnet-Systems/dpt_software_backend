<?php

namespace App\Http\Controllers\System\Api;

use App\Helpers\AppHelper;
use App\Http\Controllers\Controller;
use App\Models\System\Module;
use App\Models\System\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\System\Role;
use App\Models\System\SubModule;
use App\Models\System\User;
use App\Models\System\UserLoginLog;
use App\Models\Tenant\Project;
use App\Models\Tenant\ProjectAssignedUser;
use App\Models\Tenant\ProjectInventory;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;

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

                    if (isset($request->device_token) && !empty($request->device_token)) {
                        $req['deviceType']  = !empty($request->device_type) ? $request->device_type : '';
                        $req['deviceToken'] = $request->device_token;
                        $req['browserName'] = $request->header('User-Agent');

                        $this->deviceLogin($req, $user->toArray());
                    }

                    $minimumQuantityArr = [];

                    if ($user->role_id != User::USER_ROLE['SUPER_ADMIN']) {
                        $hostnameId = Organization::whereId($user->organization_id)->value('hostname_id');

                        $hostname = Hostname::whereId($hostnameId)->first();
                        $website = Website::whereId($hostname->website_id)->first();

                        $environment = app(\Hyn\Tenancy\Environment::class);
                        $hostname = Hostname::whereWebsiteId($website->id)->first();

                        $environment->tenant($website);
                        $environment->hostname($hostname);

                        AppHelper::setDefaultDBConnection();

                        $assignedProjectIds = ProjectAssignedUser::whereUserId($user->id)
                            ->pluck('project_id');

                        $projects = Project::whereIn('id', $assignedProjectIds)->select('id', 'name')->get();

                        if (isset($projects) && !empty($projects)) {
                            foreach ($projects as $key => $value) {
                                $minimumQuantity = ProjectInventory::whereStatus(ProjectInventory::STATUS['Active'])
                                    ->where('project_id', $value->id)
                                    ->where('minimum_quantity', '>', 0)->get();

                                if (isset($minimumQuantity) && !empty($minimumQuantity)) {
                                    $minimumQuantityArr[$key] = [
                                        'project_id' => $value->id,
                                        'project_name' => $value->name,
                                        'count' => isset($minimumQuantity) ? $minimumQuantity->count() : 0
                                    ];
                                }
                            }
                        }
                    }

                    $data = [
                        'user' => $user,
                        'minimum quantity' => $minimumQuantityArr,
                        'modules' => Module::pluck('name', 'id'),
                        'assign_modules' => Module::isAssigned($user->organization_id ?? null)->get()->toArray(),
                        'sub_modules' => SubModule::pluck('name', 'id'),
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
        $deviceToken   = (isset($req['deviceToken']) && $req['deviceToken']) ? $req['deviceToken'] : "";

        // $deviceMeta = json_encode([
        //     'device_token' . '=>' . $req['deviceToken'],
        //     'browser_name' . '=>' . $req['browserName'],
        //     'device_type' . '=>' . $req['deviceType'],
        // ]);

        if (!empty($deviceType)) {
            $device = UserLoginLog::where('user_id', '=', $userData['id'])
                ->where('device_type', $deviceType)
                ->where('device_token', $deviceToken)
                ->first();

            if (!empty($device)) {
                $device->update([
                    'device_type'   => $deviceType,
                    'device_token'  => $deviceToken,
                    'device_meta' => $req['deviceToken'],
                    'updated_ip' => request()->ip(),
                    'updated_at' => date('Y-m-d h:i:s')

                ]);
            } else {
                $deviceData = [
                    'user_id'       => $userData['id'],
                    'device_type'   => $deviceType,
                    'device_token'  => $deviceToken,
                    'device_meta' => $req['deviceToken'],
                    'created_ip' => request()->ip(),
                    'created_at' => date('Y-m-d h:i:s')
                ];

                UserLoginLog::insert($deviceData);
            }
        }
    }
}
