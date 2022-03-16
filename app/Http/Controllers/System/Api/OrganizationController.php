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

        $query = Organization::with('user')
            ->where('status', Organization::STATUS['Active'])
            ->orderBy('id', $orderBy);

        if (isset($request->search) && !empty($request->search)) {
            $search = trim(strtolower($request->search));

            $query = $query->whereRaw('LOWER(CONCAT(`name`,`email`,`phone_no`)) LIKE ?', ['%'. $search .'%']);

            $query = $query->orWhereHas('user', function($query) use($search) {
                $query->whereRaw('LOWER(`name`) LIKE ?', ['%'. $search .'%'])
                    ->where('role_id', User::USER_ROLE['COMPANY_ADMIN']);
            });
        }

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
                'per_page' => $organizations['per_page'],
                'next_page_url' => $organizations['next_page_url'],
                'prev_page_url' => $organizations['prev_page_url']
            ], 'Organization List');
        } else {
            return $this->sendResponse($results, 'Organization List');
        }
    }

    public function getOrganizationDetails(Request $request)
    {
        $organization = Organization::select('id', 'name', 'email', 'logo', 'phone_no', 'address', 'city', 'state', 'country', 'zip_code', 'subscription_id', 'status')
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
                    'email' => 'required',
                    'logo' => sprintf('mimes:%s|max:%s', config('constants.upload_image_types'), config('constants.upload_image_max_size')),
                    'phone_no' => 'required',
                    'org_domain' => 'required',
                    'address' => 'required',
                    'city' => 'required',
                    'state' => 'required',
                    'country' => 'required',
                    'zip_code' => 'required',
                ], [
                    'org_admin_name.required' => 'The organization admin name is require.',
                    'logo.max' => 'The logo must not be greater than 8mb.'
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
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
                $organization->email = $request->email;
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
                $orgUser->created_by = $user->id;
                $orgUser->email_verified_at = date('Y-m-d H:i:s');
                $orgUser->created_ip = $request->ip();
                $orgUser->updated_ip = $request->ip();

                if (!$orgUser->save()) {
                    return $this->sendError('Something went wrong while creating the organization.');
                }

                $orgUser->notify(new ResetPassword($orgUser->user_uuid));

                return $this->sendResponse($orgUser, 'Organization register successfully, also sent reset password link on organization mail.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
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
                    'logo.max' => 'The logo must not be greater than 8mb.'
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
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

                return $this->sendResponse($organization, 'Organization details updated successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function changeOrganizationStatus(Request $request, $orgId = null)
    {
        try {
            $organization = Organization::whereId($request->orgId)->first();

            if (isset($organization) && !empty($organization)) {
                $organization->status = $request->status;
                $organization->deleted_at = null;
                $organization->save();

                if ($organization->status == Organization::STATUS['Deleted']) {
                    $organization->delete();
                }

                return $this->sendResponse($organization, 'Status changed successfully.');
            }

            return $this->sendError('Organization does not exists.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
