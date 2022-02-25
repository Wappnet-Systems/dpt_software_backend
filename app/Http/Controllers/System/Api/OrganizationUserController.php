<?php

namespace App\Http\Controllers\System\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\UploadFile;
use App\Models\System\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Notifications\Organization\ResetPassword;

class OrganizationUserController extends Controller
{
    protected $uploadFile;

    public function __construct()
    {
        $this->uploadFile = new UploadFile();
    }

    public function getUsers(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');
        $user = $request->user();

        $query = User::with('organization')
            ->where('type', '!=', User::TYPE['Admin'])
            ->orderBy('id', $orderBy);

        try {
            if (isset($user) && !empty($user)) {

                if (isset($request->search) && !empty($request->search)) {

                    $search = trim(strtolower($request->search));

                    $query = $query->whereRaw('LOWER(CONCAT(`name`,`email`)) LIKE ?', ['%' . $search . '%']);
                }

                if (in_array($user->type, [User::TYPE['Company Admin']])) {

                    $query = $query->whereType(User::TYPE['Construction Site Admin']);
                } elseif (in_array($user->type, [User::TYPE['Construction Site Admin']])) {

                    $query = $query->whereIn('type', [User::TYPE['Engineer'], User::TYPE['Forman'], User::TYPE['Contractor'], User::TYPE['Sub Contractor']]);
                } elseif (in_array($user->type, [User::TYPE['Admin']])) {

                    $query = $query->whereType(User::TYPE['Company Admin']);
                }

                if (isset($user->organization_id) && !empty($user->organization_id)) {
                    $userOrganizationId = $user->organization_id;

                    $query = $query->WhereHas('organization', function ($query) use ($userOrganizationId) {
                        $query->whereId($userOrganizationId);
                    });
                }

                if ($request->exists('cursor')) {
                    $organizationUsers = $query->cursorPaginate($limit)->toArray();
                } else {
                    $organizationUsers['data'] = $query->get()->toArray();
                }

                $results = [];
                if (!empty($organizationUsers['data'])) {
                    $results = $organizationUsers['data'];
                }

                if ($request->exists('cursor')) {
                    return $this->sendResponse([
                        'lists' => $results,
                        'per_page' => $organizationUsers['per_page'],
                        'next_page_url' => $organizationUsers['next_page_url'],
                        'prev_page_url' => $organizationUsers['prev_page_url']
                    ], 'User List');
                } else {
                    return $this->sendResponse($results, 'User List');
                }
            } else {
                return $this->sendError('User does not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function getUserDetails(Request $request)
    {
        $organizationUsers = User::select('id', 'name', 'email', 'personal_email', 'phone_number', 'profile_image', 'address', 'lat', 'long', 'city', 'state', 'country', 'zip_code', 'type', 'status', 'organization_id')
            ->where('type', '!=', User::TYPE['Admin'])
            ->whereId($request->id)->first();

        if (!isset($organizationUsers) || empty($organizationUsers)) {
            return $this->sendError('User does not exists.');
        }

        return $this->sendResponse($organizationUsers, 'User details.');
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
                    'profile_image' => sprintf('mimes:%s|max:%s', config('constants.upload_image_types'), config('constants.upload_image_max_size')),
                    'type' => 'required'
                ], [
                    'profile_image.max' => 'The profile image must not be greater than 8mb.',
                    'organization_id.required' => 'Invalid organization.'
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                if (in_array($request->type, [User::TYPE['Admin'], User::TYPE['Company Admin']])) {
                    return $this->sendError('This user type is not valid.');
                }

                $usercheck = User::where('email', $request->email)->whereStatus(User::STATUS['Active'])->get();

                if (count($usercheck) && !empty($usercheck)) {
                    return $this->sendError('Email already register. Please try again.');
                }

                if (!isset($user->organization_id) && empty($user->organization_id)) {

                    return $this->sendError('Organization are not exists. Please try again.');
                }

                $orgSubUser = new User();
                $orgSubUser->user_uuid = User::generateUuid();
                $orgSubUser->name = $request->name;
                $orgSubUser->email = $request->email;
                $orgSubUser->personal_email = $request->personal_email;
                $orgSubUser->password = Hash::make($request->password);
                $orgSubUser->phone_number = !empty($request->phone_number) ? $request->phone_number : NULL;
                $orgSubUser->address = !empty($request->address) ? $request->address : NULL;
                $orgSubUser->lat = !empty($request->lat) ? $request->lat : NULL;
                $orgSubUser->long = !empty($request->long) ? $request->long : NULL;
                $orgSubUser->city = !empty($request->city) ? $request->city : NULL;
                $orgSubUser->state = !empty($request->state) ? $request->state : NULL;
                $orgSubUser->country = !empty($request->country) ? $request->country : NULL;
                $orgSubUser->zip_code = !empty($request->zip_code) ? $request->zip_code : NULL;

                $orgSubUser->type = User::saveUserType($request->type, $user->type);

                $orgSubUser->organization_id = $user->organization_id;
                $orgSubUser->email_verified_at = date('Y-m-d H:i:s');
                $orgSubUser->created_ip = $request->ip();
                $orgSubUser->updated_ip = $request->ip();

                if ($request->hasFile('profile_image')) {
                    $dirPath = str_replace(':uid:', $user->id, config('constants.users.image_path'));

                    $filePath = $this->uploadFile->uploadFileInS3($request, $dirPath, 'profile_image');

                    if (isset($filePath) && !empty($filePath)) {
                        $orgSubUser->profile_image = $filePath;
                    }
                }

                if (!$orgSubUser->save()) {
                    return $this->sendError('Something went wrong while creating the user.');
                }

                $orgSubUser->notify(new ResetPassword($orgSubUser->user_uuid));

                return $this->sendResponse($orgSubUser, 'User saved successfully, also sent reset password link on organization mail.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateUser(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {

                $validator = Validator::make($request->all(), [
                    'user_uuid' => 'required',
                    'name' => 'required',
                    'email' => 'required',
                    'personal_email' => 'required',
                    'profile_image' => sprintf('mimes:%s|max:%s', config('constants.upload_image_types'), config('constants.upload_image_max_size')),
                ], [
                    'profile_image.max' => 'The profile image must not be greater than 8mb.',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                $orgUser = User::where('user_uuid', $request->user_uuid)->where('status', User::STATUS['Active'])->where('type', '!=', User::TYPE['Admin'])->first();

                if ($user->organization_id == $orgUser->organization_id) {

                    if (isset($orgUser) && !empty($orgUser)) {

                        if ($request->filled('name')) $orgUser->name = $request->name;
                        if ($request->filled('email')) $orgUser->email = $request->email;
                        if ($request->filled('personal_email')) $orgUser->personal_email = $request->personal_email;
                        if ($request->filled('phone_number')) $orgUser->phone_number = $request->phone_number;

                        if ($request->filled('address')) {
                            $orgUser->address = $request->address;
                            $orgUser->lat = $request->lat;
                            $orgUser->long = $request->long;
                        }

                        if ($request->filled('city')) $orgUser->city = $request->city;
                        if ($request->filled('state')) $orgUser->state = $request->state;
                        if ($request->filled('country')) $orgUser->country = $request->country;
                        if ($request->filled('zip_code')) $orgUser->zip_code = $request->zip_code;

                        if ($request->hasFile('profile_image')) {
                            $dirPath = str_replace(':uid:', $user->id, config('constants.users.image_path'));

                            $this->uploadFile->deleteFileFromS3($orgUser->profile_image);

                            $filePath = $this->uploadFile->uploadFileInS3($request, $dirPath, 'profile_image');

                            if (isset($filePath) && !empty($filePath)) {
                                $orgUser->profile_image = $filePath;
                            }
                        }
                        $orgUser->updated_ip = $request->ip();
                        $orgUser->save();

                        return $this->sendResponse($orgUser, 'User Profile Updated Successfully.');
                    }
                } else {
                    return $this->sendError('You does not have permission to update user.');
                }
                return $this->sendError('User does not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function changeUserStatus(Request $request)
    {
        try {

            $user  = $request->user();

            if (isset($user) && !empty($user)) {

                $organizationUser = User::where('user_uuid', $request->user_uuid)->where('type', '!=', User::TYPE['Admin'])->first();

                if (isset($organizationUser) && !empty($organizationUser)) {

                    if ($user->organization_id == $organizationUser->organization_id) {

                        if (in_array($user->type, [User::TYPE['Company Admin']]) && in_array($organizationUser->type, [User::TYPE['Construction Site Admin']])) {
                            $organizationUser->status = $request->status;

                            if ($organizationUser->status == User::STATUS['Deleted']) {
                                $organizationUser->delete();
                            }
                        } elseif (in_array($user->type, [User::TYPE['Construction Site Admin']])) {

                            if (in_array($organizationUser->type, [User::TYPE['Engineer'], User::TYPE['Forman'], User::TYPE['Contractor'], User::TYPE['Sub Contractor']])) {

                                $organizationUser->status = $request->status;

                                if ($organizationUser->status == User::STATUS['Deleted']) {
                                    $organizationUser->delete();
                                }
                            } else {
                                return $this->sendError('User does not exists.');
                            }
                        }
                        $organizationUser->save();
                    } else {
                        return $this->sendError('You does not have permission to change status.');
                    }
                    return $this->sendResponse($organizationUser, 'Status changed successfully.');
                }
            } else {
                return $this->sendError('User does not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
