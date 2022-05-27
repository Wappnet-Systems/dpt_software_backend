<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectActivityAllocateManforce extends Model
{
    use HasFactory;

    protected $table = "projects_activities_allocate_manforces";

    protected $guarded = [];

    public function projectActivity()
    {
        return $this->belongsTo(ProjectActivity::class, 'project_activity_id', 'id')
            ->with('activitySubCategory')
            ->select('id', 'project_id', 'project_main_activity_id', 'activity_sub_category_id', 'manforce_type_id', 'name', 'start_date', 'end_date', 'actual_start_date', 'actual_end_date', 'location', 'level', 'actual_area', 'completed_area', 'unit_type_id', 'cost', 'scaffold_requirement', 'helper', 'status', 'productivity_rate', 'created_by');
    }

    public function projectManforce()
    {
        return $this->belongsTo(ProjectManforce::class, 'project_manforce_id', 'id')
            ->select('id', 'project_id', 'manforce_type_id', 'total_manforce', 'cost', 'cost_type')
            ->with('manforce');
    }
}
