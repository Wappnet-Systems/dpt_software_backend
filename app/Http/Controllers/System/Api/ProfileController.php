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
    protected $uploadFile;

    public function __construct()
    {
        $this->uploadFile = new UploadFile();
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
                return $this->sendError('Validation Error.', [$key => $value[0]], 400);
            }
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return $this->sendResponse($user, 'Password updated successfully.');
    }

    public function getProfileDetails(Request $request)
    {
        $user = $request->user();

        $user = User::with('role', 'organization')
            ->select('id', 'name', 'email', 'personal_email', 'phone_number', 'profile_image', 'address', 'lat', 'long', 'city', 'state', 'country', 'zip_code', 'po_box', 'role_id', 'organization_id')
            ->whereId($user->id)
            ->first();

        return $this->sendResponse($user, 'Get Profile Detail.');
    }

    public function updateProfileDetails(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|regex:/(.+)@(.+)\.(.+)/i|unique:users,email,' . $user->id,
            'personal_email' => 'email|regex:/(.+)@(.+)\.(.+)/i|unique:users,personal_email,' . $user->id,
            'phone_number' => 'numeric|digits_between:10,15',
            'zip_code' => 'numeric|digits_between:5,10',
            'profile_image' => sprintf('mimes:%s|max:%s', config('constants.upload_image_types'), config('constants.upload_image_max_size'))
        ], [
            'profile_image.max' => 'The profile image must not be greater than 8mb.'
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                return $this->sendError('Validation Error.', [$key => $value[0]], 400);
            }
        }
        die('hello');
        
        $user = User::whereId($user->id)->first();

        if (!isset($user) || empty($user)) {
            return $this->sendError('User not exists.');
        }

        if ($request->hasFile('profile_image')) {
            $dirPath = str_replace(':uid:', $user->id, config('constants.users.image_path'));

            $this->uploadFile->deleteFileFromS3($user->profile_image);

            $filePath = $this->uploadFile->uploadFileInS3($request, $dirPath, 'profile_image');

            if (isset($filePath) && !empty($filePath)) {
                $user->profile_image = $filePath;
            }
        }

        if ($request->filled('name')) {
            $user->name = $request->name;
        }

        if ($request->filled('email')) {
            $user->email = $request->email;
        }

        if ($request->filled('personal_email')) {
            $user->personal_email = $request->personal_email;
        }

        if ($request->filled('phone_number')) {
            $user->phone_number = $request->phone_number;
        }

        if ($request->filled('address')) {
            $user->address = $request->address;
            $user->lat = $request->lat;
            $user->long = $request->long;
        }

        if ($request->filled('city')) {
            $user->city = $request->city;
        }

        if ($request->filled('state')) {
            $user->state = $request->state;
        }

        if ($request->filled('country')) {
            $user->country = $request->country;
        }

        if ($request->filled('zip_code')) {
            $user->zip_code = $request->zip_code;
        }

        $user->updated_ip = $request->ip();
        $user->save();

        return $this->sendResponse($user, 'Profile Updated Successfully.');
    }
}
