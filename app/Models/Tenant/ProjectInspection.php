<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectInspection extends Model
{
    use HasFactory;

    protected $table = "projects_inspections";

    protected $guarded = [];
}
