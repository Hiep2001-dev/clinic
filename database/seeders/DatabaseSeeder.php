<?php

namespace Database\Seeders;

use App\Models\ClinicAppointment;
use App\Models\ClinicAdmin;
use App\Models\ClinicDoctor;
use App\Models\ClinicService;
use App\Models\ClinicTimeSlot;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminEmail = env('CLINIC_ADMIN_EMAIL');
        $adminPassword = env('CLINIC_ADMIN_PASSWORD');

        if (!$adminEmail || !$adminPassword) {
            throw new \RuntimeException('CLINIC_ADMIN_EMAIL and CLINIC_ADMIN_PASSWORD must be set in the .env file before seeding.');
        }

        ClinicAdmin::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => env('CLINIC_ADMIN_NAME', 'Quản trị viên'),
                'password' => Hash::make($adminPassword),
            ],
        );

        if (ClinicService::query()->exists()) {
            return;
        }

        $services = collect([
            ['name' => 'Nội tổng quát', 'description' => 'Khám và tư vấn sức khỏe tổng quát', 'icon' => '✚'],
            ['name' => 'Nhi khoa', 'description' => 'Chăm sóc sức khỏe cho trẻ em', 'icon' => '◉'],
            ['name' => 'Tai Mũi Họng', 'description' => 'Điều trị các bệnh lý tai, mũi, họng', 'icon' => '◌'],
            ['name' => 'Chẩn đoán hình ảnh', 'description' => 'Siêu âm, X-quang và thăm dò hình ảnh', 'icon' => '⌁'],
        ])->map(fn ($service) => ClinicService::create($service));

        $doctors = collect([
            ['name' => 'BS. Nguyễn Minh Anh', 'specialty' => 'Nội tổng quát', 'degree' => 'CKI Nội khoa'],
            ['name' => 'BS. Trần Hoàng Nam', 'specialty' => 'Nhi khoa', 'degree' => 'ThS. Nhi khoa'],
            ['name' => 'BS. Lê Thu Hà', 'specialty' => 'Tai Mũi Họng', 'degree' => 'CKII Tai Mũi Họng'],
        ])->map(fn ($doctor) => ClinicDoctor::create($doctor));

        $slots = collect(['08:00', '08:30', '09:00', '09:30', '10:00', '13:30', '14:00', '14:30', '15:00', '15:30'])
            ->flatMap(fn ($time) => [
                ClinicTimeSlot::create(['date' => now()->toDateString(), 'start_time' => $time, 'end_time' => date('H:i', strtotime($time . ' +30 minutes')), 'max_patients' => 6]),
                ClinicTimeSlot::create(['date' => now()->addDay()->toDateString(), 'start_time' => $time, 'end_time' => date('H:i', strtotime($time . ' +30 minutes')), 'max_patients' => 6]),
            ]);

        ClinicAppointment::create([
            'booking_code' => 'PK-83921', 'service_id' => $services[0]->id, 'doctor_id' => $doctors[0]->id, 'time_slot_id' => $slots->first()->id,
            'patient_name' => 'Phạm Minh Châu', 'phone' => '0901234567', 'birth_date' => '1992-05-14', 'gender' => 'Nữ',
            'address' => 'Quận 3, TP. Hồ Chí Minh', 'email' => 'chau@example.com', 'symptoms' => 'Đau đầu nhẹ và mệt mỏi', 'status' => 'confirmed',
        ]);

        ClinicAppointment::create([
            'booking_code' => 'PK-47218', 'service_id' => $services[1]->id, 'doctor_id' => $doctors[1]->id, 'time_slot_id' => $slots->get(2)->id,
            'patient_name' => 'Nguyễn Gia Huy', 'phone' => '0918765432', 'birth_date' => '2018-09-21', 'gender' => 'Nam',
            'address' => 'Bình Thạnh, TP. Hồ Chí Minh', 'symptoms' => 'Sốt nhẹ từ tối qua', 'status' => 'pending',
        ]);
    }
}
