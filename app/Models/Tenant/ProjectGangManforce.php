<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectGangManforce extends Model
{
    use HasFactory;

    protected $table = "projects_gangs_manforces";

    protected $guarded = [];

    public function projectGang()
    {
        return $this->hasOne(ProjectGang::class, 'id', 'gang_id')
            ->select('id', 'project_id', 'name', 'status');
    }
    
    public function manforce()
    {
        return $this->belongsTo(ManforceType::class, 'manforce_type_id', 'id')
            ->select('id', 'name', 'status');
    }
}
