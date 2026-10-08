<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicTimeSlot extends Model
{
    protected $fillable = ['date', 'start_time', 'end_time', 'max_patients', 'is_enabled'];
    protected $casts = ['date' => 'date:Y-m-d', 'is_enabled' => 'boolean'];

    public function appointments()
    {
        return $this->hasMany(ClinicAppointment::class, 'time_slot_id');
    }
}
