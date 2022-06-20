<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ProjectActivity extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = "projects_activities";

    protected $guarded = [];

    protected $appends = ['status_name'];

    const STATUS = [
        'Pending' => 1,
        'Start' => 2,
        'Hold' => 3,
        'Completed' => 4,
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
    
    public function scopeProjectActivities($query, $relation, $data) {
        return $query->addSelect(DB::raw(
                sprintf('(EXISTS (SELECT * FROM projects_activities_assigned_users WHERE projects_activities_assigned_users.project_activity_id = projects_activities.id AND user_id = %s)) as is_assigned', auth()->user()->id)
            )
        );
        
        /* $query->whereHas(
            $relation,
            function ($query) use ($data) {
                $query->whereIn('id', $data);
            }
        ); */
    }

    public function activitySubCategory()
    {
        return $this->belongsTo(ActivitySubCategory::class, 'activity_sub_category_id', 'id')
            ->with('activityCategory', 'unitType')
            ->select('id', 'activity_category_id', 'unit_type_id', 'name', 'status');
    }

    public function unitType()
    {
        return $this->belongsTo(UnitType::class, 'unit_type_id', 'id')
            ->select('id', 'name', 'status');
    }

    public function manforceType()
    {
        return $this->belongsTo(ManforceType::class, 'manforce_type_id', 'id')
            ->select('id', 'name', 'status');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id')
            ->select('id', 'name', 'logo', 'address', 'lat', 'long', 'city', 'state', 'country', 'zip_code', 'start_date', 'end_date', 'cost', 'status');
    }

    public function mainActivity()
    {
        return $this->belongsTo(ProjectMainActivity::class, 'project_main_activity_id', 'id')
            ->select('id', 'project_id', 'parent_id', 'name', 'status', 'created_by');
    }

    public function assignedUsers()
    {
        return $this->hasMany(ProjectActivityAssignedUser::class, 'project_activity_id', 'id');
    }

    public function projectInspections()
    {
        return $this->hasMany(ProjectInspection::class, 'project_activity_id', 'id')
            ->select('id', 'project_activity_id', 'project_allocate_material_id', 'inspection_no', 'inspection_date', 'approve_reject_date', 'location', 'document', 'inspection_type', 'type', 'inspection_status', 'status');
    }

    public function projectScaffold()
    {
        return $this->hasMany(ProjectScaffold::class, 'project_activity_id', 'id')
            ->select('id', 'project_activity_id', 'scaffold_number', 'on_hire_date', 'off_hire_date', 'width', 'length', 'height', 'area', 'volume');
    }

    public function AllocatedManforce()
    {
        return $this->hasMany(ProjectActivityAllocateManforce::class, 'project_activity_id', 'id')
            ->select('id', 'project_activity_id', 'project_manforce_id', 'date', 'total_assigned', 'total_planned', 'is_overtime', 'total_work', 'total_cost', 'productivity_rate');
    }
}
