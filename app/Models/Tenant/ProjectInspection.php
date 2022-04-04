<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\UploadFile;

class ProjectInspection extends Model
{
    use HasFactory;

    protected $table = "projects_inspections";

    protected $guarded = [];

    protected $appends = ['inspection_type_name', 'type_name', 'inspection_status_name', 'status_name', 'document_path'];

    const INC_TYPE = [
        'Internal' => 1,
        'External' => 2
    ];

    const TYPE = [
        'Activity' => 1,
        'Material' => 2
    ];

    const INC_STATUS = [
        'Pending' => 1,
        'Approved' => 2,
        'Rejected' => 3
    ];

    const STATUS = [
        'Active' => 1,
        'In Active' => 2,
        'Deleted' => 3,
    ];

    /**
     * Get the inspection type name.
     *
     * @return string
     */
    public function getinspectionTypeNameAttribute()
    {
        $flipStatus = array_flip(self::INC_TYPE);

        if (isset($flipStatus[$this->inspection_type]) && !empty($flipStatus[$this->inspection_type])) {
            return "{$flipStatus[$this->inspection_type]}";
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
        $flipStatus = array_flip(self::TYPE);

        if (isset($flipStatus[$this->type]) && !empty($flipStatus[$this->type])) {
            return "{$flipStatus[$this->type]}";
        }

        return null;
    }

    /**
     * Get the inspection status name.
     *
     * @return string
     */
    public function getInspectionStatusNameAttribute()
    {
        $flipStatus = array_flip(self::INC_STATUS);

        if (isset($flipStatus[$this->inspection_status]) && !empty($flipStatus[$this->inspection_status])) {
            return "{$flipStatus[$this->inspection_status]}";
        }

        return null;
    }

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

    public function getDocumentPathAttribute()
    {
        if ($this->document) {
            $uploadFile = new UploadFile();

            return $uploadFile->getS3FilePath('document', $this->document);
        }

        return null;
    }

    public function projectActivity()
    {
        return $this->belongsTo(ProjectActivity::class, 'project_activity_id', 'id')
            ->select('id', 'project_id', 'activity_sub_category_id', 'project_drowing_id', 'name', 'scaffold_number', 'start_date', 'end_date', 'actual_start_date', 'actual_end_date', 'location', 'level', 'actual_area', 'completed_area', 'cost', 'status', 'productivity_rate');
    }
}
