<?php

namespace App\Http\Controllers\System\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
use App\Notifications\Organization\ResetPassword;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Helpers\AppHelper;
use App\Helpers\UploadFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class OrganizationController extends Controller
{
    protected $uploadFile;

    public function __construct()
    {
        $this->uploadFile = new UploadFile();
    }

    public function getOrganizations(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = Organization::with('user', 'hostname')
            ->orderBy('id', $orderBy);

        if (isset($request->search) && !empty($request->search)) {
            $search = trim(strtolower($request->search));

            $query = $query->whereRaw('LOWER(CONCAT(`name`,`email`,`phone_no`)) LIKE ?', ['%' . $search . '%']);

            $query = $query->orWhereHas('user', function ($query) use ($search) {
                $query->whereRaw('LOWER(`name`) LIKE ?', ['%' . $search . '%'])
                    ->where('role_id', User::USER_ROLE['COMPANY_ADMIN']);
            });
        }

        $totalQuery = $query;
        $totalQuery = $totalQuery->count();

        if ($request->exists('cursor')) {
            $organizations = $query->cursorPaginate($limit)->toArray();
        } else {
            $organizations['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($organizations['data'])) {
            $results = $organizations['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'total' => $totalQuery,
                'per_page' => $organizations['per_page'],
                'next_page_url' => ltrim(str_replace($organizations['path'], "", $organizations['next_page_url']), "?cursor="),
                'prev_page_url' => ltrim(str_replace($organizations['path'], "", $organizations['prev_page_url']), "?cursor=")
            ], 'Organization List');
        } else {
            return $this->sendResponse($results, 'Organization List.');
        }
    }

    public function getOrganizationDetails(Request $request)
    {
        $organization = Organization::with('user', 'hostname')
            ->select('id', 'hostname_id', 'name', 'email', 'logo', 'phone_no', 'address', 'city', 'state', 'country', 'zip_code', 'subscription_id', 'status')
            ->whereId($request->id)
            ->first();

        if (!isset($organization) || empty($organization)) {
            return $this->sendError('Organization does not exists.');
        }

        return $this->sendResponse($organization, 'Organization details updated successfully.');
    }

    public function addOrganization(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'name' => 'required',
                    'org_admin_name' => 'required',
                    'email' => 'required|email|regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$/|exists:users,email', // |exists:organizations,email
                    'logo' => sprintf('mimes:%s|max:%s', config('constants.upload_image_types'), config('constants.upload_image_max_size')),
                    'phone_no' => 'required|numeric|digits_between:10,15',
                    'org_domain' => 'required', // |exists:hostnames,fqdn
                    'address' => 'required',
                    'city' => 'required',
                    'state' => 'required',
                    'country' => 'required',
                    'zip_code' => 'required|numeric|digits_between:5,10',
                ], [
                    'org_admin_name.required' => 'The organization admin name is require.',
                    'logo.max' => 'The logo must not be greater than 5mb.'
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                if (!in_array($user->role_id, [User::USER_ROLE['SUPER_ADMIN']])) {
                    return $this->sendError('You have not rights to create a organization.', [], 401);
                }

                if (Organization::whereEmail(strtolower($request->email))->onlyTrashed()->exists()) {
                    return $this->sendRecoveryResponse('Already added organization with same email id, do you want to recover it.', [], 400);
                }

                if (Organization::whereEmail(strtolower($request->email))->exists()) {
                    return $this->sendError('Organization already exists please try using another one.', [], 400);
                }

                // Create new website
                $website = new Website;
                $website->uuid = Organization::generateUuid($request->org_domain);

                if (!app(WebsiteRepository::class)->create($website)) {
                    return $this->sendError('Something went wrong while creating the organization.');
                }

                // Create new hostname
                $hostname = new Hostname();
                $hostname->fqdn = $request->org_domain;
                $hostname->website_id = $website->id;

                if (!$hostname->save()) {
                    return $this->sendError('Something went wrong while creating the organization.');
                }

                // Create new organization
                $organization = new Organization();
                $organization->hostname_id = $hostname->id;
                $organization->name = $request->name;
                $organization->email = strtolower($request->email);
                $organization->phone_no = $request->phone_no;
                $organization->address = $request->address;
                $organization->city = $request->city;
                $organization->state = $request->state;
                $organization->country = $request->country;
                $organization->zip_code = $request->zip_code;
                $organization->is_details_visible = $request->is_details_visible;
                $organization->created_ip = $request->ip();
                $organization->updated_ip = $request->ip();

                if (!$organization->save()) {
                    return $this->sendError('Something went wrong while creating the organization.');
                }

                if ($request->hasFile('logo')) {
                    $dirPath = str_replace(':uid:', $organization->id, config('constants.organizations.logo_path'));

                    $organization->logo = $this->uploadFile->uploadFileInS3($request, $dirPath, 'logo', "100", "100");

                    $organization->save();
                }

                // Create new organization admin
                $orgUser = new User();
                $orgUser->role_id = User::USER_ROLE['COMPANY_ADMIN'];
                $orgUser->organization_id = $organization->id;
                $orgUser->user_uuid = AppHelper::generateUuid();
                $orgUser->name = $request->org_admin_name;
                $orgUser->email = strtolower($organization->email);
                $orgUser->phone_number = $organization->phone_no;
                $orgUser->address = $organization->address;
                $orgUser->city = $organization->city;
                $orgUser->state = $organization->state;
                $orgUser->country = $organization->country;
                $orgUser->zip_code = $organization->zip_code;
                $orgUser->po_box = $request->po_box;
                $orgUser->created_by = $user->id;
                $orgUser->email_verified_at = date('Y-m-d H:i:s');
                $orgUser->created_ip = $request->ip();
                $orgUser->updated_ip = $request->ip();

                if (!$orgUser->save()) {
                    return $this->sendError('Something went wrong while creating the organization.');
                }

                $status = Password::sendResetLink(
                    $orgUser->only('email')
                );

                /* $token = base64_encode($orgUser->user_uuid . ":" . $orgUser->email);

                $orgUser->notify(new ResetPassword($token)); */

                return $this->sendResponse([], 'Organization register successfully, also sent reset password link on organization mail.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function updateOrganization(Request $request, $orgId = null)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'name' => 'required',
                    'org_admin_name' => 'required',
                    'logo' => sprintf('mimes:%s|max:%s', config('constants.upload_image_types'), config('constants.upload_image_max_size')),
                ], [
                    'org_admin_name.required' => 'The organization admin name is require.',
                    'logo.max' => 'The logo must not be greater than 5mb.'
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $organization = Organization::whereId($request->orgId)->first();

                if (!isset($organization) || empty($organization)) {
                    return $this->sendError('Organization does not exists.');
                }

                if ($request->filled('name')) $organization->name = $request->name;
                if ($request->filled('phone_no')) $organization->phone_no = $request->phone_no;
                if ($request->filled('address')) $organization->address = $request->address;
                if ($request->filled('city')) $organization->city = $request->city;
                if ($request->filled('state')) $organization->state = $request->state;
                if ($request->filled('country')) $organization->country = $request->country;
                if ($request->filled('zip_code')) $organization->zip_code = $request->zip_code;
                if ($request->filled('is_details_visible')) $organization->is_details_visible = $request->is_details_visible;
                $organization->updated_ip = $request->ip();

                if (!$organization->save()) {
                    return $this->sendError('Something went wrong while creating the organization.');
                }

                if ($request->hasFile('logo')) {
                    if (isset($organization->logo) && !empty($organization->logo)) {
                        $this->uploadFile->deleteFileFromS3($organization->logo);
                    }

                    $dirPath = str_replace(':uid:', $organization->id, config('constants.organizations.logo_path'));

                    $organization->logo = $this->uploadFile->uploadFileInS3($request, $dirPath, 'logo', "100", "100");
                    $organization->save();
                }

                if (isset($organization) && !empty($organization)) {
                    $user = User::whereOrganizationId($request->orgId)->whereRoleId(User::USER_ROLE['COMPANY_ADMIN'])->first();

                    if ($request->filled('org_admin_name')) $user->name = $request->org_admin_name;

                    $user->save();
                }

                return $this->sendResponse([], 'Organization details updated successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function changeOrganizationStatus(Request $request, $orgId = null)
    {
        try {
            $user = $request->user();

            $organization = Organization::whereId($request->orgId)->first();

            if (!in_array($request->status, Organization::STATUS)) {
                return $this->sendError('Invalid status requested.');
            }

            if (isset($organization) && !empty($organization)) {
                $organization->status = $request->status;
                $organization->deleted_at = null;
                $organization->save();

                User::whereOrganizationId($organization->id)->update([
                    'status' => $request->status
                ]);

                if ($organization->status == Organization::STATUS['Deleted']) {
                    if (!in_array($user->role_id, [User::USER_ROLE['SUPER_ADMIN']])) {
                        return $this->sendError('You have not rights to delete a organization.', [], 401);
                    }

                    $organization->delete();

                    User::whereOrganizationId($organization->id)->update([
                        'status' => $request->status
                    ]);

                    User::whereOrganizationId($organization->id)->delete();
                }

                return $this->sendResponse($organization, 'Status changed successfully.');
            }

            return $this->sendError('Organization does not exists.');
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function recoveryEmail(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'email' => "required|email|regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$/",
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                if (!in_array($user->role_id, [User::USER_ROLE['SUPER_ADMIN']])) {
                    return $this->sendError('You have not rights to recovery of organization email.', [], 401);
                }

                $organization = Organization::whereEmail(strtolower($request->email))->onlyTrashed()->first();

                if (!isset($organization) || empty($organization)) {
                    return $this->sendError('Organization does not exists.');
                }

                if (!empty($organization->deleted_at)) {
                    $organization->status = Organization::STATUS['Active'];
                    $organization->deleted_at = null;
                    $organization->save();

                    User::whereOrganizationId($organization->id)->onlyTrashed()
                        ->update([
                            'status' => User::STATUS['Active'],
                            'deleted_at' => null
                        ]);

                    return $this->sendResponse([], 'Organization email recovery successfully.');
                }
                return $this->sendError('Something went wrong while recover the organization email.', [], 400);
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
