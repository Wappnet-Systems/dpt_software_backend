<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
    public function scopeIsAssigned($query, $orgId = null)
    {
        if (!isset($orgId) || empty($orgId)) return null;

        return $query->addSelect(DB::raw(
                sprintf('*, (EXISTS (SELECT * FROM role_has_modules WHERE role_has_modules.module_id = modules.id AND role_id = %s AND organization_id = %s)) as is_assigned', User::USER_ROLE['COMPANY_ADMIN'], $orgId)
            )
        );
    }

    /**
     * A module permission can be applied to roles.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_has_modules',
            'module_id',
            'role_id'
        );
    }
}
