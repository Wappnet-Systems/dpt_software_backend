<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectActivityMaterialUses extends Model
{
    use HasFactory;

    protected $table = "projects_activities_materials_uses";

    protected $guarded = [];

    public function projectActivity()
    {
        return $this->belongsTo(ProjectActivity::class, 'project_activity_id', 'id')->with('activitySubCategory');
    }
    
    public function projectAllocateMaterial()
    {
        return $this->belongsTo(ProjectActivityAllocateMaterial::class, 'project_allocate_material_id', 'id')->with('projectInventory');
    }
}
