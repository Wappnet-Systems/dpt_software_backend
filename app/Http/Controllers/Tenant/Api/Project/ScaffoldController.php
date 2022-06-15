<?php

namespace App\Http\Controllers\Tenant\Api\Project;

use App\Helpers\AppHelper;
use App\Http\Controllers\Controller;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\ProjectActivity;
use App\Models\Tenant\ProjectScaffold;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ScaffoldController extends Controller
{
    public function __construct()
    {
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

    public function getScaffoldActivity(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {

                $query = ProjectActivity::with('projectScaffold')
                    ->whereProjectMainActivityId($request->project_main_activity_id ?? '')
                    ->where('scaffold_requirement', true)
                    ->orderBy('id', $orderBy);

                $totalQuery = $query;
                $totalQuery = $totalQuery->count();

                if ($request->exists('cursor')) {
                    $scaffoldActivity = $query->cursorPaginate($limit)->toArray();
                } else {
                    $scaffoldActivity['data'] = $query->get()->toArray();
                }

                $results = [];
                if (!empty($scaffoldActivity['data'])) {
                    $results = $scaffoldActivity['data'];
                }

                if ($request->exists('cursor')) {
                    return $this->sendResponse([
                        'lists' => $results,
                        'total' => $totalQuery,
                        'per_page' => $scaffoldActivity['per_page'],
                        'next_page_url' => ltrim(str_replace($scaffoldActivity['path'], "", $scaffoldActivity['next_page_url']), "?cursor="),
                        'prev_page_url' => ltrim(str_replace($scaffoldActivity['path'], "", $scaffoldActivity['prev_page_url']), "?cursor=")
                    ], 'Scaffold Activity List.');
                } else {
                    return $this->sendResponse($results, 'Scaffold Activity List.');
                }
            } else {
                return $this->sendError('User does not exists.', [], 400);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function addScaffoldActivity(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'project_activity_id' => 'required|exists:projects_activities,id',
                    'activity_scaffold' => 'required|json'
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $request->merge(['activity_scaffold' => json_decode($request->activity_scaffold, true)]);

                $projectScaffoldExists = ProjectScaffold::where('project_activity_id', $request->project_activity_id);

                if ($projectScaffoldExists->exists()) {
                    $projectScaffoldExists->delete();
                }

                foreach ($request->activity_scaffold as $key => $value) {
                    $projectScaffold = new ProjectScaffold();
                    $projectScaffold->project_activity_id = $request->project_activity_id;
                    $projectScaffold->scaffold_number = $value['scaffold_number'];
                    $projectScaffold->on_hire_date = date('Y-m-d', strtotime($value['on_hire_date']));
                    $projectScaffold->off_hire_date = date('Y-m-d', strtotime($value['off_hire_date']));
                    $projectScaffold->width = $value['width'];
                    $projectScaffold->length = $value['length'];
                    $projectScaffold->height = $value['height'];
                    $projectScaffold->area = $value['length'] * $value['width'];
                    $projectScaffold->volume = $value['length'] * $value['width'] * $value['height'];
                    $projectScaffold->created_ip = $request->ip();
                    $projectScaffold->updated_ip = $request->ip();
                    $projectScaffold->save();
                }

                return $this->sendResponse([], 'Activity scaffold created successfully.');
            } else {
                return $this->sendError('User does not exist');
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
