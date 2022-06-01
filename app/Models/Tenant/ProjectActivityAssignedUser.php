<?php

namespace App\Models\Tenant;

use App\Helpers\AppHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectActivityAssignedUser extends Model
{
    use HasFactory;

    protected $table = "projects_activities_assigned_users";

    protected $guarded = [];

    public function projectActivity()
    {
        return $this->belongsTo(ProjectActivity::class, 'project_activity_id', 'id');
    }

    public function user()
    {
        AppHelper::setDefaultDBConnection(true);

        return $this->belongsTo('App\Models\System\User', 'user_id', 'id');

        AppHelper::setDefaultDBConnection();
    }
}
