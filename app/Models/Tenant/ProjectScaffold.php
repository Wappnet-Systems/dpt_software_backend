<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectScaffold extends Model
{
    use HasFactory;

    protected $table = "projects_scaffolds";

    protected $guarded = [];
}
