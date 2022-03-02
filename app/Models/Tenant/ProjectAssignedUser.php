<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectAssignedUser extends Model
{
    use HasFactory;

    protected $table = "projects_assigned_users";

    protected $guarded = [];
}
