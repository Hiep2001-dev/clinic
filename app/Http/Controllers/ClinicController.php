<?php

namespace App\Http\Controllers;

use App\Models\ClinicAppointment;
use App\Models\ClinicDoctor;
use App\Models\ClinicService;
use App\Models\ClinicTimeSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClinicController extends Controller
{
    public function bootstrap()
    {
        return response()->json([
            'services' => ClinicService::where('is_active', true)->orderBy('id')->get(),
            'doctors' => ClinicDoctor::where('is_active', true)->orderBy('id')->get(),
            'slots' => $this->slotQuery(request('date', now()->toDateString()))->get(),
        ]);
    }

    public function slots(Request $request)
    {
        return response()->json($this->slotQuery($request->input('date', now()->toDateString()))->get());
    }

    public function services()
    {
        return response()->json(ClinicService::orderBy('id')->get());
    }

    public function storeService(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:10'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        return response()->json(['service' => ClinicService::create($data)], 201);
    }

    public function updateService(Request $request, ClinicService $service)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:10'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $service->update($data);
        return response()->json(['service' => $service->fresh()]);
    }

    public function destroyService(ClinicService $service)
    {
        abort_if($service->appointments()->exists(), 422, 'Không thể xóa chuyên khoa đang có lịch hẹn. Hãy tắt chuyên khoa thay thế.');
        $service->delete();
        return response()->json(['message' => 'Đã xóa chuyên khoa.']);
    }

    public function doctors()
    {
        return response()->json(ClinicDoctor::orderBy('id')->get());
    }

    public function storeDoctor(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'specialty' => ['required', 'string', 'max:120'],
            'degree' => ['nullable', 'string', 'max:120'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        return response()->json(['doctor' => ClinicDoctor::create($data)], 201);
    }

    public function updateDoctor(Request $request, ClinicDoctor $doctor)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'specialty' => ['sometimes', 'string', 'max:120'],
            'degree' => ['nullable', 'string', 'max:120'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $doctor->update($data);
        return response()->json(['doctor' => $doctor->fresh()]);
    }

    public function destroyDoctor(ClinicDoctor $doctor)
    {
        abort_if($doctor->appointments()->exists(), 422, 'Không thể xóa bác sĩ đang có lịch hẹn. Hãy tắt bác sĩ thay thế.');
        $doctor->delete();
        return response()->json(['message' => 'Đã xóa bác sĩ.']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'service_id' => ['required', 'exists:clinic_services,id'],
            'doctor_id' => ['nullable', 'exists:clinic_doctors,id'],
            'time_slot_id' => ['required', 'exists:clinic_time_slots,id'],
            'patient_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:120'],
            'symptoms' => ['nullable', 'string', 'max:1000'],
        ]);

        $appointment = DB::transaction(function () use ($data) {
            $service = ClinicService::whereKey($data['service_id'])->where('is_active', true)->firstOrFail();
            $doctor = isset($data['doctor_id'])
                ? ClinicDoctor::whereKey($data['doctor_id'])->where('is_active', true)->firstOrFail()
                : null;
            $slot = ClinicTimeSlot::whereKey($data['time_slot_id'])->lockForUpdate()->firstOrFail();
            $booked = ClinicAppointment::where('time_slot_id', $slot->id)
                ->whereNotIn('status', ['cancelled'])
                ->count();

            abort_if(date('Y-m-d', strtotime((string) $slot->date)) < today()->toDateString(), 422, 'Không thể đặt lịch trong quá khứ.');
            abort_if(ClinicAppointment::where('phone', $data['phone'])
                ->where('time_slot_id', $slot->id)
                ->whereNotIn('status', ['cancelled'])
                ->exists(), 422, 'Số điện thoại này đã có lịch trong khung giờ đã chọn.');
            abort_if(!$slot->is_enabled || !$this->isSlotWithinSchedule($slot) || $booked >= $slot->max_patients, 422, 'Khung giờ này nằm ngoài lịch làm việc hoặc đã đủ số lượng.');
            $data['booking_code'] = $this->bookingCode();

            return ClinicAppointment::create($data)->load(['service', 'doctor', 'timeSlot']);
        });

        return response()->json(['message' => 'Đặt lịch thành công.', 'appointment' => $appointment], 201);
    }

    public function lookup(Request $request)
    {
        $data = $request->validate([
            'booking_code' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:30'],
        ]);
        $rateKey = 'clinic-lookup|' . $request->ip() . '|' . strtoupper(trim($data['booking_code']));
        abort_if(RateLimiter::tooManyAttempts($rateKey, 5), 429, 'Bạn tra cứu quá nhiều lần. Vui lòng thử lại sau.');
        RateLimiter::hit($rateKey, 60);

        $appointment = ClinicAppointment::with(['service:id,name', 'doctor:id,name', 'timeSlot:id,date,start_time,end_time'])
            ->where('booking_code', strtoupper(trim($data['booking_code'])))
            ->where('phone', trim($data['phone']))
            ->latest()
            ->first();

        if (!$appointment) {
            return response()->json(['message' => 'Không tìm thấy lịch hẹn phù hợp.'], 404);
        }

        RateLimiter::clear($rateKey);
        return response()->json(['appointment' => [
            'booking_code' => $appointment->booking_code,
            'status' => $appointment->status,
            'appointment_date' => $appointment->appointment_date,
            'start_time' => $appointment->start_time,
            'end_time' => $appointment->end_time,
            'service' => $appointment->service?->only(['name']),
            'doctor' => $appointment->doctor?->only(['name']),
        ]]);
    }

    public function index(Request $request)
    {
        $request->validate([
            'date' => ['nullable', 'date'],
            'service_id' => ['nullable', 'exists:clinic_services,id'],
            'status' => ['nullable', Rule::in(['pending', 'confirmed', 'completed', 'cancelled'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $appointments = ClinicAppointment::with(['service', 'doctor', 'timeSlot'])
            ->when($request->date, fn ($q, $date) => $q->whereHas('timeSlot', fn ($slot) => $slot->whereDate('date', $date)))
            ->when($request->service_id, fn ($q, $service) => $q->where('service_id', $service))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->search, fn ($q, $search) => $q->where(fn ($inner) => $inner->where('patient_name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))
            ->latest()
            ->limit(200)
            ->get();

        return response()->json($appointments);
    }

    public function updateStatus(Request $request, ClinicAppointment $appointment)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['pending', 'confirmed', 'completed', 'cancelled'])]]);
        $appointment->update(['status' => $data['status']]);
        return response()->json(['appointment' => $appointment->fresh(['service', 'doctor', 'timeSlot'])]);
    }

    public function updateNote(Request $request, ClinicAppointment $appointment)
    {
        $data = $request->validate(['internal_note' => ['nullable', 'string', 'max:1000']]);
        $appointment->update($data);
        return response()->json(['appointment' => $appointment->fresh(['service', 'doctor', 'timeSlot'])]);
    }

    public function storeSlot(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'max_patients' => ['required', 'integer', 'min:1', 'max:50'],
            'is_enabled' => ['sometimes', 'boolean'],
        ]);
        $data['start_time'] .= ':00';
        $data['end_time'] .= ':00';

        abort_if(ClinicTimeSlot::where('date', $data['date'])->where('start_time', $data['start_time'])->exists(), 422, 'Khung giờ này đã tồn tại trong ngày.');
        $slot = ClinicTimeSlot::make($data);
        abort_if($slot->start_time >= $slot->end_time, 422, 'Giờ kết thúc phải sau giờ bắt đầu.');
        abort_if(!$this->isSlotWithinSchedule($slot), 422, 'Khung giờ phải nằm trong buổi sáng 09:00–12:30 hoặc buổi chiều 14:00–18:30.');

        return response()->json(['slot' => ClinicTimeSlot::create($data)], 201);
    }

    public function updateSlot(Request $request, ClinicTimeSlot $slot)
    {
        $data = $request->validate([
            'date' => ['sometimes', 'date', 'after_or_equal:today'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i'],
            'is_enabled' => ['sometimes', 'boolean'],
            'max_patients' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        if (isset($data['start_time'])) {
            $data['start_time'] .= ':00';
        }
        if (isset($data['end_time'])) {
            $data['end_time'] .= ':00';
        }

        $slot->fill($data);
        abort_if($slot->start_time >= $slot->end_time, 422, 'Giờ kết thúc phải sau giờ bắt đầu.');
        abort_if(!$this->isSlotWithinSchedule($slot), 422, 'Khung giờ phải nằm trong lịch làm việc của phòng khám.');
        $slot->update($data);
        return response()->json(['slot' => $slot->fresh()]);
    }

    public function destroySlot(ClinicTimeSlot $slot)
    {
        abort_if($slot->appointments()->exists(), 422, 'Không thể xóa khung giờ đã có lịch hẹn. Hãy tắt khung giờ thay thế.');
        $slot->delete();
        return response()->json(['message' => 'Đã xóa khung giờ.']);
    }

    private function slotQuery(string $date)
    {
        $query = ClinicTimeSlot::where('date', $date)->withCount([
            'appointments as booked_count' => fn ($query) => $query->whereNotIn('status', ['cancelled']),
        ]);

        $dayOfWeek = (int) date('N', strtotime($date));
        if ($dayOfWeek === 7) {
            return $query->whereRaw('1 = 0')->orderBy('start_time');
        }

        $closingTime = $dayOfWeek === 6 ? '17:00:00' : '18:30:00';
        return $query->where(function ($hours) use ($closingTime) {
            $hours->where(function ($morning) {
                $morning->where('start_time', '>=', '09:00:00')
                    ->where('end_time', '<=', '12:30:00');
            })->orWhere(function ($afternoon) use ($closingTime) {
                $afternoon->where('start_time', '>=', '14:00:00')
                    ->where('end_time', '<=', $closingTime);
            });
        })
            ->orderBy('start_time');
    }

    private function isSlotWithinSchedule(ClinicTimeSlot $slot): bool
    {
        $dayOfWeek = (int) date('N', strtotime((string) $slot->date));
        if ($dayOfWeek === 7) {
            return false;
        }

        $closingTime = $dayOfWeek === 6 ? '17:00:00' : '18:30:00';
        $morningSlot = $slot->start_time >= '09:00:00' && $slot->end_time <= '12:30:00';
        $afternoonSlot = $slot->start_time >= '14:00:00' && $slot->end_time <= $closingTime;

        return $morningSlot || $afternoonSlot;
    }

    private function bookingCode(): string
    {
        do {
            $code = 'PK-' . random_int(10000000, 99999999);
        } while (ClinicAppointment::where('booking_code', $code)->exists());
        return $code;
    }
}
