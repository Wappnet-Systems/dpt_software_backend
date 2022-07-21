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
        'role_id',
        'organization_id',
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
        'status',
        'created_by',
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

    protected $appends = ['status_name', 'profile_image_path'];

    const USER_ROLE = [
        'SUPER_ADMIN' => 1,
        'COMPANY_ADMIN' => 2,
        'CONSTRUCATION_SITE_ADMIN' => 3,
        'MANAGER' => 4,
        'PROJECT_ENGINEER' => 5,
        'QS_DEPARTMENT' => 6,
        'ENGINEER' => 10,
    ];

    const USER_ROLE_GROUP = [
        SELF::USER_ROLE['SUPER_ADMIN'] => [2],
        SELF::USER_ROLE['COMPANY_ADMIN'] => [3],
        SELF::USER_ROLE['CONSTRUCATION_SITE_ADMIN'] => [4,5,6,7,8,9,10,11,12,13,14],
        SELF::USER_ROLE['MANAGER'] => [5,6,7,8,9,10,11,12,13,14],
        SELF::USER_ROLE['PROJECT_ENGINEER'] => [10,11,12],
        SELF::USER_ROLE['ENGINEER'] => [13,14]
    ];

    const MANAGE_ROLE_GROUP = [
        SELF::USER_ROLE['COMPANY_ADMIN'] => [2,3,4,5,6,7,8,9,10,11,12,13,14],
        SELF::USER_ROLE['CONSTRUCATION_SITE_ADMIN'] => [4,5,6,7,8,9,10,11,12,13,14],
        SELF::USER_ROLE['MANAGER'] => [5,6,7,8,9,10,11,12,13,14],
    ];

    /* const TYPE = [
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
        'Storekeeper' => 13,
        'Timekeeper' => 14,
    ]; */

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

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id')
            ->select('id', 'name', 'status');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id', 'id')
            ->select('id', 'hostname_id', 'name', 'email', 'logo', 'phone_no', 'address', 'city', 'state', 'country', 'zip_code', 'status', 'is_details_visible', 'subscription_id');
    }

    /**
     * Send a password reset notification to the user.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $token = base64_encode($token . ":" . $this->email);

        $this->notify(new ResetPassword($token));
    }
}
