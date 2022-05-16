<?php

namespace App\Http\Controllers\System\Api;

use App\Helpers\AppHelper;
use App\Http\Controllers\Controller;
use App\Models\System\Organization;
use Illuminate\Http\Request;
use App\Models\System\PunchDetail;
use App\Models\System\User;
use App\Models\Tenant\Project;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PunchDetailController extends Controller
{
    public function __construct()
    {
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

    public function getUserPunchDetails(Request $request)
    {
        $user = $request->user();

        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        try {
            if (isset($user) && !empty($user)) {
                $query = PunchDetail::whereUserId($user->id)
                    ->orderBy('id', $orderBy);

                if (isset($request->punch_date) && !empty($request->punch_date)) {
                    $query = $query->whereDate('punch_date_time', date('Y-m-d', strtotime($request->punch_date)));
                } else {
                    $query = $query->whereDate('punch_date_time', date('Y-m-d'));
                }

                $totalQuery = $query;
                $totalQuery = $totalQuery->count();

                if ($request->exists('cursor')) {
                    $punchDetails = $query->cursorPaginate($limit)->toArray();
                } else {
                    $punchDetails['data'] = $query->get()->toArray();
                }

                $results = [];
                if (!empty($punchDetails['data'])) {
                    $results = $punchDetails['data'];
                }
                if ($request->exists('cursor')) {
                    return $this->sendResponse([
                        'lists' => $results,
                        'total' => $totalQuery,
                        'per_page' => $punchDetails['per_page'],
                        'next_page_url' => ltrim(str_replace($punchDetails['path'], "", $punchDetails['next_page_url']), "?cursor="),
                        'prev_page_url' => ltrim(str_replace($punchDetails['path'], "", $punchDetails['prev_page_url']), "?cursor=")
                    ], 'User punch in-out list.');
                } else {
                    return $this->sendResponse($results, 'User punch in-out list.');
                }
            } else {
                return $this->sendError('User does not exists.');
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function punchInOut(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {

                $radius = config('constants.radius');

                $projectId = Project::select('id', 'uuid', 'name', 'logo', 'address', 'lat', 'long', 'city', 'state', 'country', 'zip_code', 'start_date', 'end_date', 'cost', 'status', DB::raw("ROUND(( 3959 * acos ( cos ( radians(" . $request->latitude . ") ) * cos( radians( projects.lat ) ) * cos( radians( projects.long ) - radians(" . $request->longitude . ") ) + sin ( radians(" . $request->latitude . ") ) * sin( radians( projects.lat ) ) ) ),2) AS `distance`"))
                    ->whereId($request->project_id)
                    ->having('distance', '<=', $radius)
                    ->groupBy('id')
                    ->get();

                if (count($projectId)) {
                    $punchDetails = PunchDetail::whereDate('punch_date_time', date('Y-m-d'))->whereUserId($user->id)->orderBy('id', 'DESC')->first();

                    if (isset($punchDetails) && !empty($punchDetails)) {
                        if ($punchDetails->punch_type == PunchDetail::PUNCH_TYPE['In']) {
                            $punchDetails = new PunchDetail();
                            $punchDetails->user_id = $user->id;
                            $punchDetails->punch_date_time = date('Y-m-d h:i:s');
                            $punchDetails->punch_type = PunchDetail::PUNCH_TYPE['Out'];
                        } elseif ($punchDetails->punch_type == PunchDetail::PUNCH_TYPE['Out']) {
                            $punchDetails = new PunchDetail();
                            $punchDetails->user_id = $user->id;
                            $punchDetails->punch_date_time = date('Y-m-d h:i:s');
                            $punchDetails->punch_type = PunchDetail::PUNCH_TYPE['In'];
                        }
                    } else {
                        $punchDetails = new PunchDetail();
                        $punchDetails->user_id = $user->id;
                        $punchDetails->punch_date_time = date('Y-m-d h:i:s');
                        $punchDetails->punch_type = PunchDetail::PUNCH_TYPE['In'];
                    }
                    $punchDetails->latitude = $request->latitude;
                    $punchDetails->longitude = $request->longitude;
                    $punchDetails->created_ip = $request->ip();
                    $punchDetails->updated_ip = $request->ip();

                    if (!$punchDetails->save()) {
                        return $this->sendError('Something went wrong while creating the punch details.', [], 400);
                    }

                    return $this->sendResponse([], 'User punch details saved successfully.');
                } else {
                    return $this->sendError('Your are not under radius of project working location.', [], 400);
                }
            } else {
                return $this->sendError('User does not exists.');
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
