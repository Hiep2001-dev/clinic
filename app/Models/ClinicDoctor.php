<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicDoctor extends Model
{
    protected $fillable = ['name', 'specialty', 'degree', 'avatar', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function appointments()
    {
        return $this->hasMany(ClinicAppointment::class, 'doctor_id');
    }
}
