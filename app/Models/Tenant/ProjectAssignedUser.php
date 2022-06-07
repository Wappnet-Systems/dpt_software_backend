<?php

namespace App\Models\Tenant;

use App\Helpers\AppHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectAssignedUser extends Model
{
    use HasFactory;

    protected $table = "projects_assigned_users";

    protected $guarded = [];

    public function user()
    {
        AppHelper::setDefaultDBConnection(true);

        return $this->belongsTo('App\Models\System\User', 'user_id', 'id');

        AppHelper::setDefaultDBConnection();
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id')
            ->select('id', 'name', 'logo', 'address', 'lat', 'long', 'city', 'state', 'country', 'zip_code', 'start_date', 'end_date', 'cost', 'status');
    }
}
