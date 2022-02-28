<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubModule extends Model
{
    use HasFactory;

    protected $table = "sub_modules";

    protected $connection = 'mysql';

    protected $guarded = [];

    /**
     * Indicates if the sub model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Set the sub module created at.
     *
     * @param  string  $value
     * @return void
     */
    public function setCreatedAtAttribute($value = null)
    {
        $this->attributes['created_at'] = !empty($value) ? $value : date('Y-m-d h:i:s');
    }

    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id', 'id');
    }
}
