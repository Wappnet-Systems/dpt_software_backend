<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectRaisingMaterialRequest extends Model
{
    use HasFactory;

    protected $table = "projects_raising_material_requests";

    protected $guarded = [];

    protected $appends = ['status_name'];

    const STATUS = [
        'Pending' => 1,
        'Approved' => 2,
        'Rejected' => 3,
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
}
