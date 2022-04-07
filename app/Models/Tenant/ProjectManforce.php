<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectManforce extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = "projects_manforces";

    protected $guarded = [];

    protected $appends = ['cost_type_name'];

    const COST_TYPE = [
        'Per Hour' => 1,
        'Per Day' => 2,
    ];

    /**
     * Get the status name.
     *
     * @return string
     */
    public function getCostTypeNameAttribute()
    {
        $flipCost = array_flip(self::COST_TYPE);

        if (isset($flipCost[$this->cost_type]) && !empty($flipCost[$this->cost_type])) {
            return "{$flipCost[$this->cost_type]}";
        }

        return null;
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id')
            ->select('id', 'name', 'logo', 'address', 'lat', 'long', 'city', 'state', 'country', 'zip_code', 'start_date', 'end_date', 'cost', 'status');
    }

    public function manforce()
    {
        return $this->belongsTo(ManforceType::class, 'manforce_type_id', 'id')
            ->select('id', 'name', 'status');
    }
}
