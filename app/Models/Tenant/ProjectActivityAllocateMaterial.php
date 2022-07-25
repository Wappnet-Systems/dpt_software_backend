<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectActivityAllocateMaterial extends Model
{
    use HasFactory;

    protected $table = "projects_activities_allocate_materials";

    protected $guarded = [];

    public function projectActivity()
    {
        return $this->belongsTo(ProjectActivity::class, 'project_activity_id', 'id')
            ->with('mainActivity')
            ->select('id', 'project_id', 'project_main_activity_id', 'activity_sub_category_id', 'manforce_type_id', 'name', 'start_date', 'end_date', 'actual_start_date', 'actual_end_date', 'location', 'level', 'actual_area', 'completed_area', 'unit_type_id', 'cost', 'scaffold_requirement', 'helper', 'status', 'productivity_rate', 'created_by', 'sort_by');
    }
    
    public function projectInventory()
    {
        return $this->belongsTo(ProjectInventory::class, 'project_inventory_id', 'id')
            ->select('id', 'project_id', 'material_type_id', 'unit_type_id', 'total_quantity', 'average_cost', 'assigned_quantity', 'remaining_quantity', 'minimum_quantity', 'status')
            ->with('materialType', 'unitType');
    }

    /* Calculation of average_cost  */
    public static function calcAverageCost($projectInventoryQty = 0, $projectInventoryAvgCost = 0, $materialQty = 0, $materialCost = 0)
    {
        $totalInventoryCost = $projectInventoryQty * $projectInventoryAvgCost;

        $totalMaterialCost = $materialQty * $materialCost;

        $totalCost = $totalInventoryCost + $totalMaterialCost;
        
        $totalQty = $projectInventoryQty + $materialQty;

        $averageCost = 0;
        if ($totalQty > 0) {
            $averageCost = round($totalCost / $totalQty);
        }
        
        return $averageCost;
    }

    /* Re Calculation of average_cost  */
    public static function reCalcAverageCost($projectInventoryQty = 0, $projectInventoryAvgCost = 0, $materialQty = 0, $materialCost = 0)
    {
        $totalInventoryCost = $projectInventoryQty * $projectInventoryAvgCost;

        $totalMaterialCost = $materialQty * $materialCost;

        $totalCost = $totalInventoryCost - $totalMaterialCost;

        $totalQty = $projectInventoryQty - $materialQty;

        $averageCost = 0;
        if ($totalQty > 0) {
            $averageCost = round($totalCost / $totalQty);
        }
        
        return $averageCost;
    }
}
