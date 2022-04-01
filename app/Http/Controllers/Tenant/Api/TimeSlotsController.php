<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\TimeSlot;
use App\Helpers\AppHelper;

class TimeSlotsController extends Controller
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

    public function getTimeSlots(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = TimeSlot::orderby('id', $orderBy);

        $totalQuery = $query;
        $totalQuery = $totalQuery->count();

        if ($request->exists('cursor')) {
            $timeSlot = $query->cursorPaginate($limit)->toArray();
        } else {
            $timeSlot['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($timeSlot['data'])) {
            $results = $timeSlot['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'total' => $totalQuery,
                'per_page' => $timeSlot['per_page'],
                'next_page_url' => ltrim(str_replace($timeSlot['path'], "", $timeSlot['next_page_url']), "?cursor="),
                'prev_page_url' => ltrim(str_replace($timeSlot['path'], "", $timeSlot['prev_page_url']), "?cursor=")
            ], 'Time slot list');
        } else {
            return $this->sendResponse($results, 'Time slot list');
        }
    }
}
