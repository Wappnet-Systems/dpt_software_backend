<?php

namespace App\Models\System;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Helpers\AppHelper;
use App\Helpers\UploadFile;
use App\Notifications\ResetPassword;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use SoftDeletes;

    protected $table = "users";

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_uuid',
        'name',
        'email',
        'personal_email',
        'password',
        'phone_number',
        'profile_image',
        'address',
        'lat',
        'long',
        'city',
        'state',
        'country',
        'zip_code',
        'type',
        'status',
        'organization_id',
        'created_ip',
        'updated_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = ['type_name', 'status_name', 'profile_image_path'];

    const TYPE = [
        'Super Admin' => 1,
        'Company Admin' => 2,
        'Construction Site Admin' => 3,
        'Manager' => 4,
        'Project Engineer' => 5,
        'QS Department' => 6,
        'HSE Department' => 7,
        'Design Department' => 8,
        'Planner Engineer' => 9,
        'Engineer' => 10,
        'Foreman' => 11,
        'QA/QC' => 12,
        'Storkeeper' => 13,
        'Timekeeper' => 14,
    ];

    const STATUS = [
        'Active' => 1,
        'In Active' => 2,
        'Deleted' => 3,
    ];

    /**
     * Get the user type name.
     *
     * @return string
     */
    public function getTypeNameAttribute()
    {
        $flipTypes = array_flip(self::TYPE);

        if (isset($flipTypes[$this->type]) && !empty($flipTypes[$this->type])) {
            return "{$flipTypes[$this->type]}";
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

    /**
     * Get the full path of profile image.
     *
     * @return string
     */
    public function getProfileImagePathAttribute()
    {
        if ($this->profile_image) {
            $uploadFile = new UploadFile();

            return $uploadFile->getS3FilePath('profile_image', $this->profile_image);
        }

        return null;
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id', 'id');
    }

    public static function generateUuid()
    {
        $data = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcefghijklmnopqrstuvwxyz';

        $uuid = str_shuffle($data);
        
        return substr($uuid, 0, 32);
    }

    public static function saveUserType($RequestUserType, $authUserType)
    {
        if (in_array($authUserType, [self::TYPE['Company Admin']])) {

            return $RequestUserType = self::TYPE['Construction Site Admin'];
        } elseif (in_array($authUserType, [self::TYPE['Admin']])) {

            return $RequestUserType = self::TYPE['Company Admin'];
        } elseif (in_array($authUserType, [self::TYPE['Construction Site Admin']])) {

            if (in_array($RequestUserType, [self::TYPE['Engineer'], self::TYPE['Forman'], self::TYPE['Contractor'], self::TYPE['Sub Contractor']])) {
                return $RequestUserType;
            }
        }
        return $RequestUserType;
    }

    /**
     * Send a password reset notification to the user.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }
}
