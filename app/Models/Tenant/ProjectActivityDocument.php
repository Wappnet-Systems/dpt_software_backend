<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Helpers\UploadFile;

class ProjectActivityDocument extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = "projects_activity_documents";

    protected $guarded = [];

    protected $appends = ['status_name', 'file_type_name', 'type_name', 'file_path'];

    const STATUS = [
        'Active' => 1,
        'In Active' => 2,
        'Deleted' => 3,
    ];

    const FILE_TYPE = [
        'Image' => 1,
        'PDF' => 2,
    ];
    const TYPE = [
        'Design/Drawings' => 1,
        'Engineering Instruction' => 2,
        'Request For Information' => 3,
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

    /**
     * Get the file type name.
     *
     * @return string
     */
    public function getFileTypeNameAttribute()
    {
        $flipType = array_flip(self::FILE_TYPE);

        if (isset($flipType[$this->file_type]) && !empty($flipType[$this->file_type])) {
            return "{$flipType[$this->file_type]}";
        }

        return null;
    }

    public function getFilePathAttribute()
    {
        if ($this->path) {
            $uploadFile = new UploadFile();

            return $uploadFile->getS3FilePath('path', $this->path);
        }

        return null;
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id')
            ->select('id', 'name', 'logo', 'address', 'lat', 'long', 'city', 'state', 'country', 'zip_code', 'start_date', 'end_date', 'cost', 'status');
    }

    public function projectActivity()
    {
        return $this->belongsTo(ProjectActivity::class, 'project_activity_id', 'id')
            ->select('id', 'name', 'status');
    }
}
