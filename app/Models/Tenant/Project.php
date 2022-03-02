<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Helpers\UploadFile;

class Project extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = "projects";

    protected $guarded = [];

    protected $appends = ['status_name', 'logo_path'];

    const STATUS = [
        'Yet to Start' => 1,
        'In Progress' => 2,
        'Completed' => 3,
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
     * Get the full path of logo.
     *
     * @return string
     */
    public function getLogoPathAttribute()
    {
        if ($this->logo) {
            $uploadFile = new UploadFile();

            return $uploadFile->getS3FilePath('logo', $this->logo);
        }

        return null;
    }

    /**
     * Get all of the assigned user of the Project
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function assignedUsers(): HasMany
    {
        return $this->hasMany(ProjectAssignedUser::class, 'project_id', 'id');
    }
}
