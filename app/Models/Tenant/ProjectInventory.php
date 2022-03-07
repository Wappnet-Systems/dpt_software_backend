<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectInventory extends Model
{
    use HasFactory;

    protected $table = "projects_inventories";

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

    /* average_cost calculation */

    public static function averageCost($materialCost, $materialQty, $projectInventoryQty, $projectInventoryAvgCost)
    {
        $totalMaterialCost =  $materialCost * $materialQty;
        $totalInventoryCost =  $projectInventoryQty * $projectInventoryAvgCost;
        $totalQty = $projectInventoryQty + $materialQty;
        $totalCost = $totalMaterialCost + $totalInventoryCost;
        $averageCost = round($totalCost / $totalQty, 2);

        return $averageCost;
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'projects_id', 'id')
            ->select('id', 'name', 'logo', 'address', 'lat', 'long', 'city', 'state', 'country', 'zip_code', 'start_date', 'end_date', 'cost', 'status');
    }

    public function materials()
    {
        return $this->belongsTo(ProjectMaterial::class, 'project_material_id', 'id')->select('id', 'projects_id', 'unit_type_id', 'quantity', 'cost', 'status');
    }

    public function unitType()
    {
        return $this->belongsTo(UnitType::class, 'unit_type_id', 'id')->select('id', 'name', 'status');
    }
}
