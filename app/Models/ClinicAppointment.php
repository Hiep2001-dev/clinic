<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicAppointment extends Model
{
    protected $fillable = [
        'booking_code', 'service_id', 'doctor_id', 'time_slot_id', 'patient_name', 'phone',
        'birth_date', 'gender', 'address', 'email', 'symptoms', 'status', 'internal_note',
    ];

    protected $casts = ['birth_date' => 'date:Y-m-d'];
    protected $appends = ['start_time', 'end_time', 'appointment_date'];

    public function service() { return $this->belongsTo(ClinicService::class, 'service_id'); }
    public function doctor() { return $this->belongsTo(ClinicDoctor::class, 'doctor_id'); }
    public function timeSlot() { return $this->belongsTo(ClinicTimeSlot::class, 'time_slot_id'); }
    public function getStartTimeAttribute() { return $this->timeSlot?->start_time; }
    public function getEndTimeAttribute() { return $this->timeSlot?->end_time; }
    public function getAppointmentDateAttribute() { return $this->timeSlot?->date?->format('Y-m-d'); }
}
