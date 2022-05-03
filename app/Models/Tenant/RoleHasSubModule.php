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
    ];

    const ACTION_GROUP = [
        'list' => [2],
        'comment' => [9],
        'assign' => [19, 27, 32, 35, 36, 37],
        'approve_reject' => [19, 22, 23, 34]
    ];

    public function subModule()
    {
        return $this->belongsTo('App\Models\System\SubModule', 'sub_module_id', 'id');
    }
}
