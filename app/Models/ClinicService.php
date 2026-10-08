<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicService extends Model
{
    protected $fillable = ['name', 'description', 'icon', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function appointments()
    {
        return $this->hasMany(ClinicAppointment::class, 'service_id');
    }
}
