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
            ->select('id', 'projects_id', 'name', 'status');
    }
}
