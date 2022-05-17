<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\NcrSor;
use App\Models\Tenant\ProjectNcrSorRequest;
use App\Helpers\AppHelper;
use App\Helpers\UploadFile;

class NcrSorController extends Controller
{
    protected $uploadFile;

    public function __construct()
    {
        $this->uploadFile = new UploadFile();

        $this->middleware(function ($request, $next) {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                if ($user->role_id == User::USER_ROLE['SUPER_ADMIN']) {
                    return $this->sendError('You have no rights to access this module.', [], 401);
                }

                $hostnameId = Organization::whereId($user->organization_id)->value('hostname_id');

                $hostname = Hostname::whereId($hostnameId)->first();
                $website = Website::whereId($hostname->website_id)->first();

                $environment = app(\Hyn\Tenancy\Environment::class);
                $hostname = Hostname::whereWebsiteId($website->id)->first();

                $environment->tenant($website);
                $environment->hostname($hostname);

                AppHelper::setDefaultDBConnection();
            }

            return $next($request);
        });
    }

    public function getNcrSor(Request $request)
    {
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        try {
            $query = NcrSor::select('id', 'type', 'path', 'updated_at')
                ->orderBy('id', $orderBy);

            $totalQuery = $query;
            $totalQuery = $totalQuery->count();


            $ncrsor['data'] = $query->get()->toArray();


            $results = [];
            if (!empty($ncrsor['data'])) {
                $results = $ncrsor['data'];
            }

            return $this->sendResponse($results, 'Ncr/Sor document List.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function getNcrSorDetails(Request $request, $type = null)
    {
        $ncrsor = NcrSor::where("type", $request->type)
            ->select('id', 'type', 'path')
            ->first();

        if (!isset($ncrsor) || empty($ncrsor)) {
            return $this->sendError('Ncr/Sor document does not exists.');
        }

        return $this->sendResponse($ncrsor, 'Ncr/Sor document details.');
    }

    public function uploadNcrSorDocument(Request $request)
    {
        try {
            $user = $request->user();
            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'type' => 'required|numeric|min:1|max:2',
                    'path' => 'required|mimes:doc,docx',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                $NcrSorExist = NcrSor::where("type", $request->type)
                    ->first();

                if (!isset($NcrSorExist) || empty($NcrSorExist)) {

                    $NcrSor = new NcrSor();
                    $NcrSor->type = $request->type;
                    if ($request->hasFile('path')) {
                        $dirPath = str_replace([':uid:'], [$user->organization_id], config('constants.organizations.ncrsor.file_path'));
                        $NcrSor->path = $this->uploadFile->uploadFileInS3($request, $dirPath, 'path');
                    }

                    $NcrSor->created_ip = $request->ip();
                    $NcrSor->updated_ip = $request->ip();
                    if (!$NcrSor->save()) {
                        return $this->sendError('Something went wrong while uploading NCR/SOR document.');
                    }
                } else {

                    $oldPath = $NcrSorExist->path;

                    if ($request->hasFile('path')) {
                        $dirPath = str_replace([':uid:'], [$user->organization_id], config('constants.organizations.ncrsor.file_path'));
                        $NcrSorExist->path = $this->uploadFile->uploadFileInS3($request, $dirPath, 'path');
                    }

                    if (!$NcrSorExist->save()) {
                        return $this->sendError('Something went wrong while uploading NCR/SOR document.');
                    }

                    if (isset($oldPath) && !empty($oldPath)) {
                        $this->uploadFile->deleteFileFromS3($oldPath);
                    }
                    $NcrSor = $NcrSorExist;
                }

                return $this->sendResponse($NcrSor, 'NCR/SOR document uploaded successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function deleteNcrSor(Request $request, $type = null)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $NcrSor = NcrSor::where("type", $request->type)
                    ->first();
                $oldPath = $NcrSor->path;
                if (!isset($NcrSor) || empty($NcrSor)) {
                    return $this->sendError('NCR/SOR document does not exists.');
                }

                $NcrSor->delete();
                if (isset($oldPath) && !empty($oldPath)) {
                    $this->uploadFile->deleteFileFromS3($oldPath);
                }
                return $this->sendResponse([], 'NCR/SOR document deleted Successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function converToBlobNcrSorDocument(Request $request)
    {
        try {
            $user = $request->user();
            if (isset($user) && !empty($user)) {
                $type = $request->type;
                $id = !empty($request->id) ? $request->id : '';
                if ($id) {
                    $ncrsor = ProjectNcrSorRequest::where("id", $id)
                        ->select('id', 'type', 'path')
                        ->first();
                } else {
                    $ncrsor = NcrSor::where("type", $request->type)
                        ->select('id', 'type', 'path')
                        ->first();
                }

                if (!isset($ncrsor) || empty($ncrsor) || ($ncrsor && empty($ncrsor->file_path))) {
                    return $this->sendError('Ncr/Sor document does not exists.');
                }
                $url = $ncrsor->file_path;

                if (!file_exists(public_path() . '/doc/')) {
                    mkdir(public_path() . '/doc/', 0755, true);
                }

                $ch = curl_init($url);
                $dir = public_path() . '/doc/';
                $file_name = basename($url);
                $save_file_loc = $dir . $file_name;
                $fp = fopen($save_file_loc, 'wb');
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_exec($ch);
                curl_close($ch);
                fclose($fp);

                $file = $dir . $file_name;
                $data = fopen($file, 'rb');
                $size = filesize($file);
                $contents = fread($data, $size);
                fclose($data);
                if (file_exists($file)) {
                    unlink($file);
                }
                return response($contents, 200);
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function addUpdateRequest(Request $request)
    {
        try {
            $user = $request->user();
            echo "lll";
            exit;
            if (isset($user) && !empty($user)) {
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
