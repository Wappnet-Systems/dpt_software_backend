<?php

namespace App\Http\Controllers\System\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Notifications\Organization\ResetPassword;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Helpers\AppHelper;
use App\Helpers\UploadFile;

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

        $query = User::with('role', 'organization')
            ->where('role_id', '!=', User::USER_ROLE['SUPER_ADMIN'])
            ->orderBy('id', $orderBy);

        try {
            if (isset($user) && !empty($user)) {
                if (isset($request->search) && !empty($request->search)) {
                    $search = trim(strtolower($request->search));

                    $query = $query->whereRaw('LOWER(CONCAT(`name`,`email`)) LIKE ?', ['%' . $search . '%']);
                }

                if (in_array($user->role_id, [User::USER_ROLE['SUPER_ADMIN']])) {
                    $query = $query->whereRoleId(User::USER_ROLE['COMPANY_ADMIN']);
                } else if (in_array($user->role_id, [User::USER_ROLE['COMPANY_ADMIN']])) {
                    $query = $query->whereNotIn('role_id', [User::USER_ROLE['SUPER_ADMIN'], User::USER_ROLE['COMPANY_ADMIN']]);
                } else if (in_array($user->role_id, [User::USER_ROLE['CONSTRUCATION_SITE_ADMIN']])) {
                    $query = $query->whereNotIn('role_id', [User::USER_ROLE['SUPER_ADMIN'], User::USER_ROLE['COMPANY_ADMIN'], User::USER_ROLE['CONSTRUCATION_SITE_ADMIN']]);
                } else if (in_array($user->role_id, [User::USER_ROLE['MANAGER']])) {
                    $query = $query->whereNotIn('role_id', [User::USER_ROLE['SUPER_ADMIN'], User::USER_ROLE['COMPANY_ADMIN'], User::USER_ROLE['CONSTRUCATION_SITE_ADMIN'], User::USER_ROLE['MANAGER']]);
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
        $organizationUsers = User::with('role', 'organization')
            ->select('id', 'user_uuid', 'name', 'email', 'personal_email', 'phone_number', 'profile_image', 'address', 'lat', 'long', 'city', 'state', 'country', 'zip_code', 'status', 'role_id', 'organization_id')
            ->where('role_id', '!=', User::USER_ROLE['SUPER_ADMIN'])
            ->whereUserUuid($request->id)
            ->first();

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
                if (!in_array($user->role_id, [User::USER_ROLE['SUPER_ADMIN'], User::USER_ROLE['COMPANY_ADMIN'], User::USER_ROLE['CONSTRUCATION_SITE_ADMIN'], User::USER_ROLE['MANAGER']])) {
                    return $this->sendError('You have no rights to add User.');
                } else if ($user->role_id == User::USER_ROLE['SUPER_ADMIN'] && !in_array($request->role_id, [User::USER_ROLE['COMPANY_ADMIN']])) {
                    return $this->sendError('You have no rights to add User.');
                } else if ($user->role_id == User::USER_ROLE['COMPANY_ADMIN'] && !in_array($request->role_id, [User::USER_ROLE['CONSTRUCATION_SITE_ADMIN']])) {
                    return $this->sendError('You have no rights to add User.');
                } else if ($user->role_id == User::USER_ROLE['CONSTRUCATION_SITE_ADMIN'] && !in_array($request->role_id, [User::USER_ROLE['MANAGER']])) {
                    return $this->sendError('You have no rights to add User.');
                } else if ($user->role_id == User::USER_ROLE['MANAGER'] && in_array($request->role_id, [User::USER_ROLE['SUPER_ADMIN'], User::USER_ROLE['COMPANY_ADMIN'], User::USER_ROLE['CONSTRUCATION_SITE_ADMIN']])) {
                    return $this->sendError('You have no rights to add User.');
                } else {
                    $validator = Validator::make($request->all(), [
                        'name' => 'required',
                        'email' => 'required',
                        'personal_email' => 'required',
                        'password' => 'required',
                        'profile_image' => sprintf('mimes:%s|max:%s', config('constants.upload_image_types'), config('constants.upload_image_max_size')),
                        'role_id' => 'required|exists:roles,id'
                    ], [
                        'profile_image.max' => 'The profile image must not be greater than 8mb.',
                    ]);

                    if ($validator->fails()) {
                        foreach ($validator->errors()->messages() as $key => $value) {
                            return $this->sendError('Validation Error.', [$key => $value[0]]);
                        }
                    }

                    if (User::where('email', strtolower($request->email))->whereStatus(User::STATUS['Active'])->exists()) {
                        return $this->sendError('Email already register. Please try again.');
                    }

                    if ($user->role_id != User::USER_ROLE['SUPER_ADMIN']) {
                        if (!Organization::whereId($user->organization_id)->whereStatus(Organization::STATUS['Active'])->exists()) {
                            return $this->sendError('Organization are not exists. Please try again.');
                        }
                    }

                    $orgSubUser = new User();
                    $orgSubUser->role_id = $request->role_id;
                    $orgSubUser->organization_id = $user->organization_id;
                    $orgSubUser->user_uuid = AppHelper::generateUuid();
                    $orgSubUser->name = $request->name;
                    $orgSubUser->email = strtolower($request->email);
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
                    $orgSubUser->email_verified_at = date('Y-m-d H:i:s');
                    $orgSubUser->created_by = $user->id;
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
                }
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateUser(Request $request, $userUuid = null)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $orgUser = User::where('user_uuid', $request->userUuid)
                    ->where('status', User::STATUS['Active'])
                    ->where('role_id', '!=', User::USER_ROLE['SUPER_ADMIN'])
                    ->first();

                if (!isset($orgUser) || empty($orgUser)) {
                    return $this->sendError('User dose not exists.');
                } else if (!in_array($user->role_id, [User::USER_ROLE['SUPER_ADMIN'], User::USER_ROLE['COMPANY_ADMIN'], User::USER_ROLE['CONSTRUCATION_SITE_ADMIN'], User::USER_ROLE['MANAGER']])) {
                    return $this->sendError('You have no rights to update User.');
                } else if ($user->role_id == User::USER_ROLE['SUPER_ADMIN'] && !in_array($orgUser->role_id, [User::USER_ROLE['COMPANY_ADMIN']])) {
                    return $this->sendError('You have no rights to update User.');
                } else if ($user->role_id == User::USER_ROLE['COMPANY_ADMIN'] && !in_array($orgUser->role_id, [User::USER_ROLE['CONSTRUCATION_SITE_ADMIN']])) {
                    return $this->sendError('You have no rights to update User.');
                } else if ($user->role_id == User::USER_ROLE['CONSTRUCATION_SITE_ADMIN'] && !in_array($orgUser->role_id, [User::USER_ROLE['MANAGER']])) {
                    return $this->sendError('You have no rights to update User.');
                } else if ($user->role_id == User::USER_ROLE['MANAGER'] && in_array($orgUser->role_id, [User::USER_ROLE['SUPER_ADMIN'], User::USER_ROLE['COMPANY_ADMIN'], User::USER_ROLE['CONSTRUCATION_SITE_ADMIN']])) {
                    return $this->sendError('You have no rights to update User.');
                } else if ($user->role_id != User::USER_ROLE['SUPER_ADMIN'] && $user->organization_id != $orgUser->organization_id) {
                    return $this->sendError('You have no rights to update User.');
                } else {
                    $validator = Validator::make($request->all(), [
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

                    if ($request->filled('name')) $orgUser->name = $request->name;
                    if ($request->filled('email')) $orgUser->email = strtolower($request->email);
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
                        if (isset($orgUser->profile_image) && !empty($orgUser->profile_image)) {
                            $this->uploadFile->deleteFileFromS3($orgUser->profile_image);
                        }

                        $dirPath = str_replace(':uid:', $user->id, config('constants.users.image_path'));

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
                return $this->sendError('User does not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function changeUserStatus(Request $request, $userUuid = null)
    {
        try {
            $user  = $request->user();

            if (isset($user) && !empty($user)) {
                $orgUser = User::where('user_uuid', $request->userUuid)
                    ->where('role_id', '!=', User::USER_ROLE['SUPER_ADMIN'])
                    ->first();

                if (!isset($orgUser) || empty($orgUser)) {
                    return $this->sendError('User dose not exists.');
                } else if (!in_array($user->role_id, [User::USER_ROLE['SUPER_ADMIN'], User::USER_ROLE['COMPANY_ADMIN'], User::USER_ROLE['CONSTRUCATION_SITE_ADMIN'], User::USER_ROLE['MANAGER']])) {
                    return $this->sendError('You have no rights to update User.');
                } else if ($user->role_id == User::USER_ROLE['SUPER_ADMIN'] && !in_array($orgUser->role_id, [User::USER_ROLE['COMPANY_ADMIN']])) {
                    return $this->sendError('You have no rights to update User.');
                } else if ($user->role_id == User::USER_ROLE['COMPANY_ADMIN'] && !in_array($orgUser->role_id, [User::USER_ROLE['CONSTRUCATION_SITE_ADMIN']])) {
                    return $this->sendError('You have no rights to update User.');
                } else if ($user->role_id == User::USER_ROLE['CONSTRUCATION_SITE_ADMIN'] && !in_array($orgUser->role_id, [User::USER_ROLE['MANAGER']])) {
                    return $this->sendError('You have no rights to update User.');
                } else if ($user->role_id == User::USER_ROLE['MANAGER'] && in_array($orgUser->role_id, [User::USER_ROLE['SUPER_ADMIN'], User::USER_ROLE['COMPANY_ADMIN'], User::USER_ROLE['CONSTRUCATION_SITE_ADMIN']])) {
                    return $this->sendError('You have no rights to update User.');
                } else if ($user->role_id != User::USER_ROLE['SUPER_ADMIN'] && $user->organization_id != $orgUser->organization_id) {
                    return $this->sendError('You have no rights to update User.');
                } else {
                    $orgUser->status = $request->status;
                    $orgUser->deleted_at = null;
                    $orgUser->save();

                    if ($orgUser->status == User::STATUS['Deleted']) {
                        $orgUser->delete();
                    }

                    return $this->sendResponse($orgUser, 'Status changed successfully.');
                }
            } else {
                return $this->sendError('User does not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
