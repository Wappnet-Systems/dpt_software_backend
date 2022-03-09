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
}
