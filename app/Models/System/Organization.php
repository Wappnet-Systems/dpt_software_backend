<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Hyn\Tenancy\Models\Hostname;
use App\Helpers\AppHelper;
use App\Helpers\UploadFile;

class Organization extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = "organizations";

    protected $connection = 'mysql';

    protected $guarded = [];

    protected $appends = ['status_name', 'logo_path'];

    const STATUS = [
        'Active' => 1,
        'In Active' => 2,
        'Deleted' => 3,
        'Failure' => 4,
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

    public function hostname()
    {
        return $this->belongsTo(Hostname::class)->with('website')->select('id', 'fqdn', 'website_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'organization_id', 'id')
            ->select('user_uuid', 'name', 'email', 'personal_email', 'password', 'phone_number', 'profile_image', 'address', 'lat', 'long', 'city', 'state', 'country', 'zip_code', 'po_box', 'status', 'role_id', 'organization_id');
    }

    public static function generateUuid($orgDomain = null)
    {
        $data = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcefghijklmnopqrstuvwxyz';

        $uuid = str_replace(".", "_", sprintf("%s_%s", $orgDomain, str_shuffle($data)));

        return substr($uuid, 0, 32);
    }
}
