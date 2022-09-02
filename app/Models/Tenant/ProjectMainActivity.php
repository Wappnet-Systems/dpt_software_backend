<?php

namespace App\Models\Tenant;

use App\Models\System\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectMainActivity extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = "projects_main_activities";

    protected $guarded = [];

    protected $appends = ['status_name', 'project_activities_count'];

    const STATUS = [
        'Active' => 1,
        'In Active' => 2,
        'Deleted' => 3,
    ];

    /**
     * Get the status name.
     *
     * @return string
     */
    public function getStatusNameAttribute()
    {
        $flipStatus = array_flip(self::STATUS);

        if (isset($flipStatus[$this->status]) && !empty($flipStatus[$this->status])) {
            return "{$flipStatus[$this->status]}";
        }

        return null;
    }

    /**
     * Get the status name.
     *
     * @return string
     */
    public function getProjectActivitiesCountAttribute()
    {
        return ProjectActivity::whereProjectMainActivityId($this->id)->count();
    }
    
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id')
            ->select('id', 'name', 'logo', 'address', 'lat', 'long', 'city', 'state', 'country', 'zip_code', 'start_date', 'end_date', 'cost', 'status');
    }

    public function proActivities()
    {
        return $this->hasMany(ProjectActivity::class, 'project_main_activity_id', 'id')
            ->with('projectInspections', 'assignedUsers', 'activitySubCategory', 'unitType', 'manforceType')
            ->select('id', 'project_id', 'project_main_activity_id', 'activity_sub_category_id', 'manforce_type_id', 'name', 'start_date', 'end_date', 'actual_start_date', 'actual_end_date', 'location', 'level', 'actual_area', 'completed_area', 'unit_type_id', 'cost', 'scaffold_requirement', 'helper', 'status', 'productivity_rate', 'created_by', 'sort_by')
            ->orderBy('sort_by', 'ASC');
    }

    public function projectActivities()
    {
        $query = $this->hasMany(ProjectActivity::class, 'project_main_activity_id', 'id')
            ->with('projectInspections', 'assignedUsers', 'activitySubCategory', 'unitType', 'manforceType')
            ->select('id', 'project_id', 'project_main_activity_id', 'activity_sub_category_id', 'manforce_type_id', 'name', 'start_date', 'end_date', 'actual_start_date', 'actual_end_date', 'location', 'level', 'actual_area', 'completed_area', 'unit_type_id', 'cost', 'scaffold_requirement', 'helper', 'status', 'productivity_rate', 'created_by', 'sort_by')
            ->orderBy('sort_by', 'ASC');

        if (auth()->user()->role_id != User::USER_ROLE['MANAGER']) {
            $assignProActivityIds = ProjectActivityAssignedUser::whereUserId(auth()->user()->id)
                ->pluck('project_activity_id')
                ->toArray();

            $query->whereIn('id', $assignProActivityIds)
                ->orderBy('sort_by', 'ASC');
        }

        return $query;
    }

    public function parents()
    {
        return $this->hasMany(ProjectMainActivity::class, 'parent_id', 'id')
            ->with([
                'parents',
                'projectActivities'/*  => function ($query) {
                    if (auth()->user()->role_id != User::USER_ROLE['MANAGER']) {
                        $assignProActivityIds = ProjectActivityAssignedUser::whereUserId(auth()->user()->id)
                            ->pluck('project_activity_id')
                            ->toArray();

                        $query->whereIn('id', $assignProActivityIds)
                            ->orderBy('sort_by', 'ASC');
                    }
                } */
            ])
            ->select('id', 'project_id', 'parent_id', 'name', 'status', 'created_by', 'sort_by')
            ->orderBy('sort_by', 'ASC');
    }
}
