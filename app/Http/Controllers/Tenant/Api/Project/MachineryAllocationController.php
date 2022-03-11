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
use App\Models\Tenant\Machinery;
use App\Helpers\AppHelper;

class MachineryAllocationController extends Controller
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

    public function getAllocateMachineries(Request $request)
    {
        try {
            $timeSlot = TimeSlot::all();

            if (!isset($timeSlot) || empty($timeSlot)) {
                return $this->sendError('Time slot not available.');
            }

            $machineriesIds = explode(',', $request->machinery_ids);

            $timeSlotMachineryArr = [];

            foreach ($timeSlot as $key => $timeSlotValue) {

                $timeSlotData = ['id' => $timeSlotValue->id, 'start_time' => $timeSlotValue->start_time, 'end_time' => $timeSlotValue->end_time];

                $allocatedMachinery = ProjectActivityAllocateMachinery::with('activityCategory', 'machineries')->select('id', 'project_activity_id', 'machinery_id', 'date', 'time_slots');
                if (isset($request->project_activity_id) && !empty($request->project_activity_id)) {
                    $allocatedMachinery = $allocatedMachinery->whereProjectActivityId($request->project_activity_id);
                }
                $allocatedMachinery = $allocatedMachinery->whereRaw('FIND_IN_SET(' . $timeSlotValue->id . ',time_slots)')
                    ->whereDate('date', '>=', date('Y-m-d', strtotime($request->start_date)))
                    ->whereDate('date', '<=', date('Y-m-d', strtotime($request->end_date)))
                    ->whereIn('machinery_id', $machineriesIds)
                    ->get()->toArray();

                $timeSlotMachineryArr[$timeSlotValue->id]['time_slot'] = $timeSlotData;

                if (isset($allocatedMachinery) && !empty($allocatedMachinery)) {
                    $machinery = [];
                    foreach ($allocatedMachinery as $key => $value) {
                        $machinery = !empty($value) ? $value['machineries'] : null;
                    }

                    $timeSlotMachineryArr[$timeSlotValue->id]['machinery'] = $machinery;
                    $timeSlotMachineryArr[$timeSlotValue->id]['allocated_machinery'] = $allocatedMachinery;
                }
            }

            return $this->sendResponse(array_values($timeSlotMachineryArr), 'Project activity allocation machinery List.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function allocateMachinery(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'project_activity_id' => 'required|exists:activity_categories,id',
                    'machinery_ids' => 'required',
                    'time_slots' => 'required',
                    'date' => 'required|date_format:Y-m-d',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }
                $machineriesIds = explode(',', $request->machinery_ids);

                $isMachineriesExists = Machinery::whereIn('id', $machineriesIds)->exists();

                if (!$isMachineriesExists) {
                    return $this->sendError('Machineries does not exists.');
                }

                $timeSlotsIds = explode(',', $request->time_slots);

                $isTimeSlotsExists = TimeSlot::whereIn('id', $timeSlotsIds)->exists();

                if (!$isTimeSlotsExists) {
                    return $this->sendError('Invalid slots.');
                }

                if (date('Y-m-d', strtotime($request->date)) < date('Y-m-d')) {
                    return $this->sendError('Do not select past date.');
                }

                foreach ($machineriesIds as $machineryValue) {
                    $machineryAllocation = new ProjectActivityAllocateMachinery();
                    $machineryAllocation->project_activity_id = $request->project_activity_id;
                    $machineryAllocation->machinery_id = $machineryValue;
                    $machineryAllocation->date = date('Y-m-d', strtotime($request->date));
                    $machineryAllocation->time_slots = $request->time_slots;
                    $machineryAllocation->assign_by = $user->id;
                    $machineryAllocation->created_ip = $request->ip();
                    $machineryAllocation->updated_ip = $request->ip();
                    $machineryAllocation->save();
                }
                return $this->sendResponse([], 'Project activity allocation machinery created successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function unAllocateMachinery(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'project_activity_id' => 'required|exists:activity_categories,id',
                    'machinery_ids' => 'required',
                    'time_slots' => 'required',
                    'date' => 'required|date_format:Y-m-d',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                $machineriesIds = explode(',', $request->machinery_ids);

                $isMachineriesExists = Machinery::whereIn('id', $machineriesIds)->exists();

                if (!$isMachineriesExists) {
                    return $this->sendError('Machineries does not exists.');
                }

                $timeSlotsIds = explode(',', $request->time_slots);

                $isTimeSlotsExists = TimeSlot::whereIn('id', $timeSlotsIds)->exists();

                if (!$isTimeSlotsExists) {
                    return $this->sendError('Invalid slots.');
                }

                if (date('Y-m-d', strtotime($request->date)) < date('Y-m-d')) {
                    return $this->sendError('Do not select past date.');
                }
                $machineryAllocation = ProjectActivityAllocateMachinery::whereProjectActivityId($request->project_activity_id)
                    ->whereDate('date', date('Y-m-d', strtotime($request->date)))
                    ->get();

                foreach ($machineriesIds as $key => $value) {
                    $machineryAllocation[$key]->machinery_id = $value;
                    $machineryAllocation[$key]->time_slots = $request->time_slots;
                    $machineryAllocation[$key]->updated_ip = $request->ip();
                    $machineryAllocation[$key]->save();
                }

                return $this->sendResponse([], 'Project activity allocation machinery updated successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
