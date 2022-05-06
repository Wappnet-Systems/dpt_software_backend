<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Helpers\UploadFile;

class NcrSor extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = "ncr_sor";

    protected $guarded = [];

    protected $appends = ['file_path', 'type_name'];

    const TYPE = [
        'NCR' => 1,
        'SOR' => 2,
    ];

    public function getFilePathAttribute()
    {
        if ($this->path) {
            $uploadFile = new UploadFile();

            return $uploadFile->getS3FilePath('path', $this->path);
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
