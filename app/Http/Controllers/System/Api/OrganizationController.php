<?php

namespace App\Http\Controllers\System\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\UploadFile;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;

class OrganizationController extends Controller
{
    protected $upload_file;

    public function __construct()
    {
        $this->upload_file = new UploadFile();
    }

    public function getOrganizations(Request $request)
    {
    }

    public function addOrganization(Request $request)
    {
        $user = $request->user();

        if (isset($user) && !empty($user)) {

            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required',
                'phone_no' => 'required',
                'address' => 'required',
                'city' => 'required',
                'state' => 'required',
                'country' => 'required',
                'zip_code' => 'required',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]]);
                }
            }

            $orgDomain = $request->get('org-domain');
            // Create new website
            $website = new Website;
            $website->uuid = $this->generateUuid($orgDomain);
            if (!app(WebsiteRepository::class)->create($website)) {
                return redirect()->back()->with('error', 'Unknown error while creating the organization.');
            }

        } else {
            return $this->sendError('User not exists.');
        }
    }

    public function generateUuid(string $orgDomain): string
    {
        $data = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcefghijklmnopqrstuvwxyz';
        $uuid = str_replace(".", "_", "mr_" . $orgDomain . "_" . str_shuffle($data));
        return substr($uuid, 0, 32);
    }
}
