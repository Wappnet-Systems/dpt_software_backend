<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailFormat extends Model
{
    use HasFactory;

    protected $table = "email_formats";

    protected $connection = 'mysql';
    
    protected $guarded = [];
}
