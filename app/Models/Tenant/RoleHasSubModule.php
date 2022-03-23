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
}
