<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PunchDetail extends Model
{
    use HasFactory;

    protected $table = "punch_details";

    protected $connection = 'mysql';

    protected $appends = ['punch_name'];

    const PUNCH_TYPE = [
        'In' => 1,
        'Out' => 2,
    ];

    public function getPunchNameAttribute()
    {
        $flipStatus = array_flip(self::PUNCH_TYPE);

        if (isset($flipStatus[$this->punch_type]) && !empty($flipStatus[$this->punch_type])) {
            return "{$flipStatus[$this->punch_type]}";
        }

        return null;
    }
}
