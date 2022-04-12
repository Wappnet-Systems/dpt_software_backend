<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectManforceProductivity extends Model
{
    use HasFactory;

    protected $table = "project_manforces_productivities";

    protected $guarded = [];
}
