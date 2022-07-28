<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialApprovalLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'material_approval_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'reference_number',
        'approval_status',
        'status',
        'created_ip',
        'updated_ip',
        'created_at',
        'updated_at'
    ];

    protected $appends = ['approval_status_name', 'status_name'];

    const APPROVAL_STATUS = [
        'Pending' => 1,
        'Approved' => 2,
        'Rejected' => 3
    ];

    const STATUS = [
        'Active' => 1,
        'In Active' => 2,
        'Deleted' => 3
    ];


    /**
     * Get the approval status name.
     *
     * @return string
     */
    public function getApprovalStatusNameAttribute()
    {
        $flipStatus = array_flip(self::APPROVAL_STATUS);

        if (isset($flipStatus[$this->approval_status]) && !empty($flipStatus[$this->approval_status])) {
            return "{$flipStatus[$this->approval_status]}";
        }

        return null;
    }

    /**
     * get status name
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
