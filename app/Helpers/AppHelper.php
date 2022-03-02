<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Helpers;

use Illuminate\Support\Facades\Config;

/**
 * Description of CommonTask
 *
 * @author kishan
 */
class AppHelper {

    public function __construct() {
    }

    public static function setDefaultDBConnection($isDefault = false) {
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
}
