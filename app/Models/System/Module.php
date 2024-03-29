<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Module extends Model
{
    use HasFactory;

    protected $table = "modules";

    protected $connection = 'mysql';

    protected $guarded = [];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Set the module created at.
     *
     * @param  string  $value
     * @return void
     */
    public function setCreatedAtAttribute($value = null)
    {
        $this->attributes['created_at'] = !empty($value) ? $value : date('Y-m-d h:i:s');
    }

    /**
     * For return true if module has been assigned to particular organization
     *
     * @param  object  $query
     * @param  string  $orgId
     * @return void
     */
    public function scopeIsAssigned($query, $orgId = null, $roleId = User::USER_ROLE['COMPANY_ADMIN'])
    {
        if (!isset($orgId) || empty($orgId)) {
            return $query->addSelect(DB::raw(
                sprintf('*, (EXISTS (SELECT * FROM role_has_modules WHERE role_has_modules.module_id = modules.id AND role_id = %s)) as is_assigned', User::USER_ROLE['SUPER_ADMIN'])
                )
            );
        } else {
            return $query->addSelect(DB::raw(
                    sprintf('*, (EXISTS (SELECT * FROM role_has_modules WHERE role_has_modules.module_id = modules.id AND role_id = %s AND organization_id = %s)) as is_assigned', $roleId, $orgId)
                )
            );
        }
    }

    public function subModule()
    {
        return $this->hasMany(SubModule::class, 'module_id', 'id')
            ->where('id', '!=', 1);
    }

    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            'role_has_modules',
            'module_id',
            'role_id'
        );
    }
}
