<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityCategory extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = "activity_categories";

    protected $guarded = [];

    protected $appends = ['status_name'];

    const STATUS = [
        'Active' => 1,
        'In Active' => 2,
        'Deleted' => 3,
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

    public function activitySubCategories()
    {
        return $this->hasMany(ActivitySubCategory::class, 'activity_category_id', 'id');
    }
}
