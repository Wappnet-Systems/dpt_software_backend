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
use App\Models\Tenant\RoleHasSubModule;

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
}
