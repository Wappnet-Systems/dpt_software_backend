<?php

namespace App\Http\Controllers\Tenant\Api\Project;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\ProjectActivityAllocateMachinery;
use App\Models\Tenant\TimeSlot;
use App\Models\Tenant\RoleHasSubModule;
use App\Helpers\AppHelper;

class MachineryAllocationController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                if ($user->role_id == User::USER_ROLE['SUPER_ADMIN']) {
                    return $this->sendError('You have no rights to access this module.', [], 401);
                }

                // if (!AppHelper::roleHasModulePermission('Planning and Scheduling', $user)) {
                //     return $this->sendError('You have no rights to access this module.', [], 401);
                // }

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

    public function getAllocateMachineries(Request $request)
    {
        try {
            $user = $request->user();

            // if (!AppHelper::roleHasSubModulePermission('Planned Machinerry', RoleHasSubModule::ACTIONS['list'], $user)) {
            //     return $this->sendError('You have no rights to access this action.');
            // }

            $timeSlots = TimeSlot::get();

            if (!isset($timeSlots) || empty($timeSlots)) {
                return $this->sendError('Time slot not available.');
            }

            $machineriesIds = explode(',', $request->project_machinery_ids);

            $begin = new \DateTime($request->start_date);
            $end = new \DateTime($request->end_date);

            $timeSlotMachinery = [];
            for ($i = $begin; $i <= $end; $i->modify('+1 day')) {
                $currDate = $i->format("Y-m-d");

                foreach ($timeSlots as $timeSlotKey => $timeSlotVal) {
                    $timeSlotMachinery[$currDate][$timeSlotVal->id]['time_slot'] = [
                        'id' => $timeSlotVal->id,
                        'start_time' => $timeSlotVal->start_time,
                        'end_time' => $timeSlotVal->end_time
                    ];

                    $allocatedMachinery = ProjectActivityAllocateMachinery::with('projectActivity', 'projectMachineries')
                        ->select('id', 'project_activity_id', 'project_machinery_id', 'date', 'time_slots');

                    $allocatedMachinery = $allocatedMachinery->whereRaw('FIND_IN_SET(' . $timeSlotVal->id . ',time_slots)')
                        ->whereDate('date', '=', $currDate)
                        ->whereIn('project_machinery_id', $machineriesIds)
                        ->get()
                        ->toArray();

                    $timeSlotMachinery[$currDate][$timeSlotVal->id]['allocated_machinery'] = $allocatedMachinery;
                }
            }

            return $this->sendResponse($timeSlotMachinery, 'Allocated project machinery list.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function allocateMachinery(Request $request)
    {
        try {
            $user = $request->user();

            // if (!AppHelper::roleHasSubModulePermission('Planned Machinerry', RoleHasSubModule::ACTIONS['create'], $user)) {
            //     return $this->sendError('You have no rights to access this action.');
            // }

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'project_activity_id' => 'required|exists:projects_activities,id',
                    'project_machinery_id' => 'required|exists:projects_machineries,id',
                    'time_slots' => 'required',
                    'date' => 'required|date_format:Y-m-d',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                $timeSlotsIds = explode(',', $request->time_slots);

                $existSlotsCnt = TimeSlot::whereIn('id', $timeSlotsIds)->count();

                if ($existSlotsCnt < count($timeSlotsIds)) {
                    return $this->sendError('Invalid slots.');
                }

                if (date('Y-m-d', strtotime($request->date)) < date('Y-m-d')) {
                    return $this->sendError('Please do not select the past date.');
                }

                $allocateMachinery = ProjectActivityAllocateMachinery::whereProjectActivityId($request->project_activity_id)
                    ->where('project_machinery_id',$request->project_machinery_id)
                    ->whereDate('date', date('Y-m-d', strtotime($request->date)))
                    ->first();

                if (isset($allocateMachinery) && !empty($allocateMachinery)) {
                    $existSlots = explode(',', $allocateMachinery->time_slots);
                    $newSlots = explode(',', $request->time_slots);
                    $slots = array_unique(array_merge($existSlots, $newSlots));

                    $allocateMachinery->time_slots = !empty($slots) ? implode(',', $slots) : null;
                    $allocateMachinery->assign_by = $user->id;
                    $allocateMachinery->updated_ip = $request->ip();
                    $allocateMachinery->save();
                } else {
                    $allocateMachinery = new ProjectActivityAllocateMachinery();
                    $allocateMachinery->project_activity_id = $request->project_activity_id;
                    $allocateMachinery->project_machinery_id = $request->project_machinery_id;
                    $allocateMachinery->date = date('Y-m-d', strtotime($request->date));
                    $allocateMachinery->time_slots = $request->time_slots;
                    $allocateMachinery->assign_by = $user->id;
                    $allocateMachinery->created_ip = $request->ip();
                    $allocateMachinery->updated_ip = $request->ip();
                    $allocateMachinery->save();
                }

                $allocateMachinery = ProjectActivityAllocateMachinery::with('projectActivity', 'projectMachineries')
                    ->whereId($allocateMachinery->id)
                    ->first();

                return $this->sendResponse($allocateMachinery, 'Machinery allocated to activity successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function deleteAllocateMachinery(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            // if (!AppHelper::roleHasSubModulePermission('Planned Machinerry', RoleHasSubModule::ACTIONS['delete'], $user)) {
            //     return $this->sendError('You have no rights to access this action.');
            // }

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'time_slots' => 'required',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                $timeSlotsIds = explode(',', $request->time_slots);

                $existSlotsCnt = TimeSlot::whereIn('id', $timeSlotsIds)->count();

                if ($existSlotsCnt < count($timeSlotsIds)) {
                    return $this->sendError('Invalid slots.');
                }

                $allocateMachinery = ProjectActivityAllocateMachinery::whereId($request->id)->first();

                if (!isset($allocateMachinery) || empty($allocateMachinery)) {
                    return $this->sendError('Machinery not allocated in activity.');
                }

                if (date('Y-m-d', strtotime($allocateMachinery->date)) < date('Y-m-d')) {
                    return $this->sendError('You can not delete the past allocated machinery.');
                }

                $existSlots = explode(',', $allocateMachinery->time_slots);
                $newSlots = explode(',', $request->time_slots);
                $slots = array_unique(array_diff($existSlots, $newSlots));

                $allocateMachinery->time_slots = !empty($slots) ? implode(',', $slots) : null;
                $allocateMachinery->updated_ip = $request->ip();
                $allocateMachinery->save();

                if (empty($allocateMachinery->time_slots)) {
                    $allocateMachinery->delete();
                }

                return $this->sendResponse([], 'Allocate machinery deleted successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
