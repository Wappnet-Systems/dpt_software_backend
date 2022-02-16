<?php

namespace App\Http\Controllers\System\Api;

use App\Http\Controllers\Controller;
use App\Models\System\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\System\UserLoginLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Helpers\UploadFile;

class ProfileController extends Controller
{
    protected $upload_file;

    public function __construct()
    {
        $this->upload_file = new UploadFile();
    }

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
            'old_password' => [function ($attribute, $value, $fail) {
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

    public function getUserDetails(Request $request)
    {
        $user = $request->user();

        $user = User::where('id', $user->id)->select('id', 'name', 'email', 'profile_image')->first();

        return $this->sendResponse($user, 'Get Profile Detail.');
    }

    public function updateUserDetails(Request $request)
    {
        $user = $request->user();

        $user = User::find($user->id);

        $validator = Validator::make($request->all(), [
            'name' => 'required'
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                return $this->sendError('Validation Error.', [$key => $value[0]]);
            }
        }
        $updateArr = [];
        if ($request->hasFile('profile_image')) {
            $dirPath = str_replace(':uid:', $user->id, config('constants.users.image_path'));

            $this->upload_file->deleteFileFromS3($user->profile_image);

            $filePath = $this->upload_file->uploadFileInS3($request, $dirPath, 'profile_image');

            if (isset($filePath) && !empty($filePath)) {
                $updateArr['profile_image'] = $filePath;
                User::where('id', $user->id)->update($updateArr);
            }
        }
        $updateArr = [
            'name' => $request->name,
            'updated_at' => date('Y-m-d H:i:s'),
            'created_ip' => $request->ip(),
            'updated_ip' => $request->ip()
        ];
        User::where('id', $user->id)->update($updateArr);

        return $this->sendResponse($user, 'Profile Updated Successfully.');
    }
}
