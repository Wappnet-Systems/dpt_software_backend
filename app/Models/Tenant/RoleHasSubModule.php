<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleHasSubModule extends Model
{
    use HasFactory;

    protected $table = "role_has_sub_modules";

    protected $guarded = [];

    const ACTIONS = [
        'list' => 'is_list',
        'create' => 'is_create',
        'edit' => 'is_edit',
        'delete' => 'is_delete',
        'view' => 'is_view',
        'comment' => 'is_comment',
        'assign' => 'is_assign',
        'approve_reject' => 'is_approve_reject',
    ];

    const ACTION_GROUP = [
        'list' => ['User Management', 'Project Management'],
        'edit' => ['Stock Management', 'Overtime', 'Activity Progress'],
        'comment' => ['Gantt Chart', 'Inspection'],
        'assign' => ['Method Statement', 'Activity Document Management', 'NCR/SOR Request', 'Machinery Allocation', 'Material Allocation', 'Manforce Allocation', 'Overtime'],
        'approve_reject' => ['Raising Material Requisition', 'Raising Site Instruction', 'NCR/SOR Request', 'Material Transfer Request', 'Inspection']
    ];

    public function subModule()
    {
        return $this->belongsTo('App\Models\System\SubModule', 'sub_module_id', 'id');
    }
}
