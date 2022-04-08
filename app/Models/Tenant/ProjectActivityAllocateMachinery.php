<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectActivityAllocateMachinery extends Model
{
    use HasFactory;

    protected $table = "projects_activities_allocate_machineries";

    protected $guarded = [];

    public function projectActivity()
    {
        return $this->belongsTo(ProjectActivity::class, 'project_activity_id', 'id');
    }

    public function projectMachineries()
    {
        return $this->belongsTo(ProjectMachinery::class, 'project_machinery_id', 'id')->select('id', 'project_id', 'name', 'status');
    }
}
