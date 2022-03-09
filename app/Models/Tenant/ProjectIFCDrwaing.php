<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectIFCDrwaing extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = "projects_ifc_drawings";

    protected $guarded = [];

    protected $appends = ['status_name', 'type_name'];

    const STATUS = [
        'Active' => 1,
        'In Active' => 2,
        'Deleted' => 3,
    ];

    const TYPE = [
        'Image' => 1,
        'PDF' => 2,
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

    /**
     * Get the type name.
     *
     * @return string
     */
    public function getTypeNameAttribute()
    {
        $flipType = array_flip(self::TYPE);

        if (isset($flipType[$this->type]) && !empty($flipType[$this->type])) {
            return "{$flipType[$this->type]}";
        }

        return null;
    }
}
