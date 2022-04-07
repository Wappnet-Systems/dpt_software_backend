<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Helpers\UploadFile;

class MethodStatement extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = "methods_statements";

    protected $guarded = [];

    protected $appends = ['file_path'];

    public function getFilePathAttribute()
    {
        if ($this->path) {
            $uploadFile = new UploadFile();

            return $uploadFile->getS3FilePath('path', $this->path);
        }

        return null;
    }

    public function projectActivity()
    {
        return $this->belongsTo(ProjectActivity::class, 'project_activity_id', 'id')
            ->select('id', 'project_id', 'activity_sub_category_id', 'name', 'scaffold_number', 'start_date', 'end_date', 'actual_start_date', 'actual_end_date', 'location', 'level', 'actual_area', 'completed_area', 'cost', 'status', 'productivity_rate');
    }
}
