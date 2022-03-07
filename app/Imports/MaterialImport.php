<?php

namespace App\Imports;

use App\Models\Tenant\ProjectMaterial;
use App\Models\Tenant\ProjectInventory;
use App\Models\Tenant\UnitType;
use App\Models\Tenant\MaterialType;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class MaterialImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        Validator::make($row, [
            'cost' => 'required',
        ])->validate();

        $projectId = request()->all();

        $unitTypeExists = UnitType::whereName($row['unit_type_name'])
            ->first();

        if (!isset($unitTypeExists) || empty($unitTypeExists)) {
            $unitType = new UnitType();
            $unitType->name = $row['unit_type_name'];
            $unitType->save();
        }

        $MaterialTypeExists = MaterialType::whereName($row['material_name'])
            ->first();

        if (!isset($MaterialTypeExists) || empty($MaterialTypeExists)) {
            $materialType = new MaterialType();
            $materialType->name = $row['material_name'];
            $materialType->save();
        }

        $projectMaterial = new ProjectMaterial();
        $projectMaterial->projects_id = $projectId['project_id'];
        $projectMaterial->material_type_id = !empty($MaterialTypeExists) ? $MaterialTypeExists->id : $materialType->id;
        $projectMaterial->unit_type_id = !empty($unitTypeExists) ? $unitTypeExists->id : $unitType->id;
        $projectMaterial->quantity = $row['quantity'];
        $projectMaterial->cost = $row['cost'];
        $projectMaterial->created_by = Auth::user()->id;
        $projectMaterial->created_ip = Request::ip();
        $projectMaterial->updated_ip = Request::ip();

        if ($projectMaterial->save()) {

            $projectInventoryExists = ProjectInventory::whereProjectsId($projectMaterial->projects_id)
                ->whereMaterialTypeId($projectMaterial->material_type_id)
                ->whereUnitTypeId($projectMaterial->unit_type_id)
                ->first();

            if (isset($projectInventoryExists) && !empty($projectInventoryExists)) {
                $projectInventoryExists->total_quantity = $projectInventoryExists->total_quantity + $projectMaterial->quantity;
                $projectInventoryExists->remaining_quantity = $projectInventoryExists->remaining_quantity + $projectMaterial->quantity;
                $projectInventoryExists->average_cost = ProjectInventory::averageCost($projectMaterial->cost, $projectMaterial->quantity, $projectInventoryExists->total_quantity, $projectInventoryExists->average_cost);
                $projectInventoryExists->updated_ip = Request::ip();
                $projectInventoryExists->save();
            } else {
                $projectInventory = new ProjectInventory();
                $projectInventory->projects_id = $projectMaterial->projects_id;
                $projectInventory->material_type_id = $projectMaterial->material_type_id;
                $projectInventory->unit_type_id = $projectMaterial->unit_type_id;
                $projectInventory->total_quantity = $projectMaterial->quantity;
                $projectInventory->average_cost = $projectMaterial->cost;
                $projectInventory->assigned_quantity = 0;
                $projectInventory->remaining_quantity = $projectMaterial->quantity;
                $projectInventory->minimum_quantity = 0;
                $projectInventory->updated_ip = Request::ip();
                $projectInventory->save();
            }
        }
    }
}
