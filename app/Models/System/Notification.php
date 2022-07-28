<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = "notifications";

    protected $connection = 'mysql';
    
    protected $guarded = [];

    const TYPE = [
        'User' => 1,
        'Admin' => 2,
    ];
}
