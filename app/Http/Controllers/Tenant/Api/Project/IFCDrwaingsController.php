<?php

namespace App\Http\Controllers\Tenant\Api\Project;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\ProjectIFCDrwaing;
use App\Models\Tenant\ProjectActivity;
use App\Helpers\AppHelper;
use App\Helpers\UploadFile;

class IFCDrwaingsController extends Controller
{
    protected $uploadFile;

    public function __construct()
    {
        $this->uploadFile = new UploadFile();

        $this->middleware(function ($request, $next) {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                if ($user->role_id == User::USER_ROLE['SUPER_ADMIN']) {
                    return $this->sendError('You have no rights to access this module.');
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

    public function getIFCDrwaings(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ProjectIFCDrwaing::with('project')
            ->whereProjectId($request->project_id ?? '')
            ->whereStatus(ProjectIFCDrwaing::STATUS['Active'])
            ->orderby('id', $orderBy);

        if (isset($request->search) && !empty($request->search)) {
            $search = trim(strtolower($request->search));

            $query = $query->whereRaw('LOWER(CONCAT(`name`)) LIKE ?', ['%' . $search . '%']);

            $query = $query->orWhereHas('project', function ($query) use ($search) {
                $query->whereRaw('LOWER(CONCAT(`name`)) LIKE ?', ['%' . $search . '%']);
            });
        }

        if ($request->exists('cursor')) {
            $projectIFCDrawing = $query->cursorPaginate($limit)->toArray();
        } else {
            $projectIFCDrawing['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($projectIFCDrawing['data'])) {
            $results = $projectIFCDrawing['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'per_page' => $projectIFCDrawing['per_page'],
                'next_page_url' => $projectIFCDrawing['next_page_url'],
                'prev_page_url' => $projectIFCDrawing['prev_page_url']
            ], 'Project ifc Drawing List');
        } else {
            return $this->sendResponse($results, 'Project ifc Drawing List');
        }
    }

    public function getIFCDrwaingDetails(Request $request)
    {
        $projectIFCDrawing = ProjectIFCDrwaing::with('project')
            ->select('id', 'project_id', 'name', 'path', 'location', 'area', 'type', 'status')
            ->whereId($request->id)
            ->first();

        if (!isset($projectIFCDrawing) || empty($projectIFCDrawing)) {
            return $this->sendError('Project ifc drawing does not exist.');
        }

        return $this->sendResponse($projectIFCDrawing, 'Project ifc drawing details.');
    }

    public function addIFCDrwaing(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'project_id' => 'required|exists:projects,id',
                    'name' => 'required',
                    'path' => sprintf('mimes:%s|max:%s', 'pdf,jpeg,jpg,bmp,png', config('constants.organizations.projects.ifc_drawings.upload_image_max_size')),
                    'location' => 'required',
                    'area' => 'required',
                ], [
                    'path.max' => 'The file must not be greater than 10mb.',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                $IFCDrawing = new ProjectIFCDrwaing();
                $IFCDrawing->project_id = $request->project_id;
                $IFCDrawing->name = $request->name;
                $IFCDrawing->location = $request->location;
                $IFCDrawing->area = $request->area;
                $IFCDrawing->created_by = $user->id;
                $IFCDrawing->created_ip = $request->ip();
                $IFCDrawing->updated_ip = $request->ip();

                if ($request->hasFile('path')) {
                    $dirPath = str_replace([':uid:', ':project_uuid:'], [$user->organization_id, $request->project_id], config('constants.organizations.projects.ifc_drawings.file_path'));

                    $IFCDrawing->path = $this->uploadFile->uploadFileInS3($request, $dirPath, 'path');

                    if ($request->file('path')->getClientOriginalExtension() == 'pdf') {
                        $IFCDrawing->type = ProjectIFCDrwaing::TYPE['PDF'];
                    } else {
                        $IFCDrawing->type = ProjectIFCDrwaing::TYPE['Image'];
                    }
                }

                if (!$IFCDrawing->save()) {
                    return $this->sendError('Something went wrong while creating the project ifc drawing');
                }

                return $this->sendResponse($IFCDrawing, 'Project ifc drawing created successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateIFCDrwaing(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'id' => 'required',
                    'name' => 'required',
                    'path' => sprintf('mimes:%s|max:%s', 'pdf,jpeg,jpg,bmp,png', config('constants.organizations.projects.ifc_drawings.upload_image_max_size')),
                    'location' => 'required',
                    'area' => 'required',
                ], [
                    'path.max' => 'The file must not be greater than 10mb.',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                $projectIFCDrawing = ProjectIFCDrwaing::whereId($request->id)
                    ->first();

                if (!isset($projectIFCDrawing) || empty($projectIFCDrawing)) {
                    return $this->sendError('Project ifc drawing does not exist.');
                }

                if ($request->filled('name')) $projectIFCDrawing->name = $request->name;
                if ($request->filled('location')) $projectIFCDrawing->location = $request->location;
                if ($request->filled('area')) $projectIFCDrawing->area = $request->area;

                if ($request->hasFile('path')) {
                    if (isset($projectIFCDrawing->path) && !empty($projectIFCDrawing->path)) {
                        $this->uploadFile->deleteFileFromS3($projectIFCDrawing->path);
                    }

                    $dirPath = str_replace([':uid:', ':project_uuid:'], [$user->organization_id, $request->project_id], config('constants.organizations.projects.ifc_drawings.file_path'));

                    $projectIFCDrawing->path = $this->uploadFile->uploadFileInS3($request, $dirPath, 'path');

                    if ($request->file('path')->getClientOriginalExtension() == 'pdf') {
                        $projectIFCDrawing->type = ProjectIFCDrwaing::TYPE['PDF'];
                    } else {
                        $projectIFCDrawing->type = ProjectIFCDrwaing::TYPE['Image'];
                    }
                }
                $projectIFCDrawing->updated_ip = $request->ip();

                if (!$projectIFCDrawing->save()) {
                    return $this->sendError('Something went wrong while updating the project ifc drawing');
                }

                return $this->sendResponse($projectIFCDrawing, 'Project ifc drawing updated successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function deleteIFCDrwaing(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]]);
                }
            }

            $projectIFCDrawing = ProjectIFCDrwaing::whereId($request->id)
                ->first();

            if (!isset($projectIFCDrawing) || empty($projectIFCDrawing)) {
                return $this->sendError('Project ifc drawing does not exist.');
            }

            if (ProjectActivity::whereIn('status', [ProjectActivity::STATUS['Start'], ProjectActivity::STATUS['Hold'], ProjectActivity::STATUS['Pending']])
                ->whereProjectDrowingId($request->id)
                ->exists()) {
                return $this->sendError('Project ifc drawing assign to project activities.');
            }

            if (isset($projectIFCDrawing->path) && !empty($projectIFCDrawing->path)) {
                $this->uploadFile->deleteFileFromS3($projectIFCDrawing->path);
            }

            $projectIFCDrawing->delete();

            return $this->sendResponse([], 'Project ifc drawing deleted successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function changeIFCDrwaingStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]]);
                }
            }

            $projectIFCDrawing = ProjectIFCDrwaing::whereId($request->id)
                ->first();

            if (!isset($projectIFCDrawing) || empty($projectIFCDrawing)) {
                return $this->sendError('Project ifc drawing does not exist.');
            }

            $projectIFCDrawing->deleted_at = null;
            $projectIFCDrawing->status = $request->status;
            $projectIFCDrawing->save();

            if ($projectIFCDrawing->status == ProjectIFCDrwaing::STATUS['Deleted']) {
                if (isset($projectIFCDrawing->path) && !empty($projectIFCDrawing->path)) {
                    $this->uploadFile->deleteFileFromS3($projectIFCDrawing->path);
                }

                $projectIFCDrawing->delete();
            }

            return $this->sendResponse($projectIFCDrawing, 'Status changed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
