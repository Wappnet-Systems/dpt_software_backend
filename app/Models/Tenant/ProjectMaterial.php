<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectMaterial extends Model
{
    use HasFactory;

    protected $table = "projects_materials";

    protected $guarded = [];

    protected $appends = ['status_name'];

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

    public static function projectInventoryCostCalculation($projectMaterial)
    {
        $projectInventory = ProjectInventory::whereProjectId($projectMaterial->project_id)
            ->whereMaterialTypeId($projectMaterial->material_type_id)
            ->whereUnitTypeId($projectMaterial->unit_type_id)
            ->first();

        if (isset($projectInventory) && !empty($projectInventory)) {
            $projectInventory->average_cost = ProjectInventory::calcAverageCost($projectInventory->remaining_quantity, $projectInventory->average_cost, $projectMaterial->quantity, $projectMaterial->cost);
            $projectInventory->total_quantity = $projectInventory->total_quantity + $projectMaterial->quantity;
            $projectInventory->remaining_quantity = $projectInventory->remaining_quantity + $projectMaterial->quantity;
            $projectInventory->updated_ip = request()->ip();
            $projectInventory->save();
        } else {
            $projectInventory = new ProjectInventory();
            $projectInventory->project_id = $projectMaterial->project_id;
            $projectInventory->material_type_id = $projectMaterial->material_type_id;
            $projectInventory->unit_type_id = $projectMaterial->unit_type_id;
            $projectInventory->total_quantity = $projectMaterial->quantity;
            $projectInventory->average_cost = $projectMaterial->cost;
            $projectInventory->assigned_quantity = 0;
            $projectInventory->remaining_quantity = $projectMaterial->quantity;
            $projectInventory->minimum_quantity = 0;
            $projectInventory->created_ip = request()->ip();
            $projectInventory->updated_ip = request()->ip();
            $projectInventory->save();
        }
    }
}
