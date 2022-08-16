<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Helpers;

use Illuminate\Support\Facades\Config;
use App\Models\System\RoleHasModule;
use App\Models\System\Module;
use App\Models\System\SubModule;
use App\Models\Tenant\ProjectManforce;
use App\Models\Tenant\RoleHasSubModule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Description of CommonTask
 *
 * @author kishan
 */
class AppHelper
{

    public function __construct()
    {
    }

    public static function setDefaultDBConnection($isDefault = false)
    {
        if ($isDefault) {
            return Config::set('database.default', 'mysql');
        }

        Config::set('database.default', 'tenant');
    }

    public static function generateUuid()
    {
        $data = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcefghijklmnopqrstuvwxyz';

        $uuid = str_shuffle($data);

        return substr($uuid, 0, 32);
    }

    public static function roleHasModulePermission($moduleName = null, $user = null)
    {
        if (empty($moduleName) || empty($user)) {
            return false;
        }

        $moduleId = Module::whereName($moduleName)->value('id');

        if (!isset($moduleId) || empty($moduleId)) {
            return false;
        }

        return RoleHasModule::whereModuleId($moduleId)
            ->whereRoleId($user->role_id)
            ->whereOrganizationId($user->organization_id)
            ->exists();
    }

    public static function roleHasSubModulePermission($subModuleName = null, $action = null, $user = null)
    {
        if (empty($subModuleName) || empty($action) || empty($user)) {
            return false;
        }

        self::setDefaultDBConnection(true);

        $subModuleId = SubModule::whereName($subModuleName)->value('id');

        if (!isset($subModuleId) || empty($subModuleId)) {
            return false;
        }

        self::setDefaultDBConnection();

        return RoleHasSubModule::whereSubModuleId($subModuleId)
            ->whereRoleId($user->role_id)
            ->where($action, true)
            ->exists();
    }

    public static function calculateManforeCost($cost = 0, $costType = null, $assinedManforce = 0, $projectWorkingHour = 0, $overtimeHours = 0)
    {
        if (!isset($costType) || empty($costType)) {
            return 0;
        }

        $totalCost = 0;

        if (isset($overtimeHours) && !empty($overtimeHours)) {
            if ($costType == ProjectManforce::COST_TYPE['Per Hour']) {
                $totalCost = $cost * $assinedManforce * $overtimeHours * ProjectManforce::OVERTIME_COST_RATE;
            }
            
            if ($costType == ProjectManforce::COST_TYPE['Per Day']) {
                $perManCost = ($cost * $overtimeHours / $projectWorkingHour) * ProjectManforce::OVERTIME_COST_RATE;

                $totalCost = $perManCost * $assinedManforce;
            }
        } else {
            if ($costType == ProjectManforce::COST_TYPE['Per Hour']) {
                $totalCost = $cost * $projectWorkingHour * $assinedManforce;
            }

            if ($costType == ProjectManforce::COST_TYPE['Per Day']) {
                $totalCost = $cost * $assinedManforce;
            }
        }
        
        return round($totalCost, 2);
    }

    public static function fixedActivityDate($date = null, $totalManforce = 0, $totalArea = 0, $perManProdRate = 0, $type = 'fix_start_date')
    {
        if (!isset($date) || !isset($totalManforce) || !isset($totalArea)) {
            return $date;
        }

        $date = new Carbon($date);

        $totalDaysToWork = ceil($totalArea / ($perManProdRate * $totalManforce)) - 1;

        $updatedDate = $date;

        if ($type == 'fix_start_date') {
            $updatedDate = $date->subDays($totalDaysToWork);
        } else if ($type == 'fix_end_date') {
            $updatedDate = $date->addDays($totalDaysToWork);
        }

        return $updatedDate;
    }
}
