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
            ->select('id', 'project_id', 'activity_sub_category_id', 'name', 'scaffold_number', 'start_date', 'end_date', 'actual_start_date', 'actual_end_date', 'location', 'level', 'actual_area', 'completed_area', 'cost', 'status', 'productivity_rate')
            ->with('activitySubCategory');
    }

    public function projectManforce()
    {
        return $this->belongsTo(ProjectManforce::class, 'project_manforce_id', 'id')
            ->select('id', 'project_id', 'manforce_type_id', 'total_manforce', 'cost', 'cost_type')
            ->with('manforce');
    }
}
