<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectManforceProductivity extends Model
{
    use HasFactory;

    protected $table = "projects_manforces_productivities";

    protected $guarded = [];


    public function manforceType()
    {
        return $this->belongsTo(ManforceType::class, 'manforce_type_id', 'id')
            ->select('id', 'name', 'status');
    }

    public function unitType()
    {
        return $this->belongsTo(UnitType::class, 'unit_type_id', 'id')
            ->select('id', 'name', 'status');
    }

    public function activitySubCategories()
    {
        return $this->belongsTo(ActivitySubCategory::class, 'activity_sub_category_id', 'id')
            ->select('id', 'name', 'status');
    }
}
