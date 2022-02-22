<?php

namespace App\Http\Controllers\System\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\UploadFile;
use Illuminate\Support\Facades\Validator;

class OrganizationUserController extends Controller
{
    protected $uploadFile;

    public function __construct()
    {
        $this->uploadFile = new UploadFile();
    }

    public function getUsers(Request $request)
    {
    }

    public function getUserDetails(Request $request)
    {
    }

    public function addUser(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'name' => 'required',
                    'email' => 'required',
                    'personal_email' => 'required',
                    'password' => 'required',
                    'logo' => sprintf('mimes:%s|max:%s', config('constants.upload_image_types'), config('constants.upload_image_max_size')),
                    'organization_id' => 'required',
                    'type' => 'required'
                ], [
                    'logo.max' => 'The logo must not be greater than 8mb.'
                ]);
            }

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]]);
                }
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateUser(Request $request)
    {
    }

    public function changeUserStatus(Request $request)
    {
    }
}
