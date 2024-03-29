<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectMaterialRaisingRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "projects_materials_raising_requests";

    protected $guarded = [];

    protected $appends = ['status_name'];

    const STATUS = [
        'Pending' => 1,
        'Approved' => 2,
        'Rejected' => 3,
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

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id')
            ->select('id', 'name', 'logo', 'address', 'lat', 'long', 'city', 'state', 'country', 'zip_code', 'start_date', 'end_date', 'cost', 'status');
    }

    public function materialType()
    {
        return $this->belongsTo(MaterialType::class, 'material_type_id', 'id')
            ->select('id', 'name', 'status');
    }

    public function unitType()
    {
        return $this->belongsTo(UnitType::class, 'unit_type_id', 'id')
            ->select('id', 'name', 'status');
    }

    public function materialApprovalLog()
    {
        return $this->belongsTo(MaterialApprovalLog::class, 'material_approval_id', 'id')
            ->select('id', 'name', 'reference_number', 'approval_status', 'status');
    }
}
