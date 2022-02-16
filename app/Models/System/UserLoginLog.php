<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLoginLog extends Model
{
    use HasFactory;

    protected $table = "users_login_logs";

    protected $connection = 'mysql';
    
    protected $guarded = [];

    protected $appends = ['device_type_name'];

    const DEVICE_TYPE = [
        'Android' => 1,
        'Apple' => 2,
    ];

    /**
     * Get the status name.
     *
     * @return string
     */
    public function getDeviceTypeNameAttribute()
    {
        $flipDeviceTypes = array_flip(self::DEVICE_TYPE);

        if (isset($flipDeviceTypes[$this->device_type]) && !empty($flipDeviceTypes[$this->device_type])) {
            return "{$flipDeviceTypes[$this->device_type]}";
        }

        return null;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
