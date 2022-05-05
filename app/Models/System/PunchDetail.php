<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PunchDetail extends Model
{
    use HasFactory;

    protected $table = "punch_details";

    protected $connection = 'mysql';
}
