# Carely Clinic

Ứng dụng đặt lịch khám trực tuyến cho phòng khám đa khoa, xây dựng với Laravel REST API và Vue.js.

## Chạy ứng dụng

```powershell
php artisan serve
npm run dev
```

Mở `http://127.0.0.1:8000/clinic`.

## Khởi tạo dữ liệu

Database cần có bốn bảng clinic:

```powershell
php artisan migrate --path=database/migrations/2026_10_07_000001_create_clinic_services_table.php --force
php artisan migrate --path=database/migrations/2026_10_07_000002_create_clinic_doctors_table.php --force
php artisan migrate --path=database/migrations/2026_10_07_000003_create_clinic_time_slots_table.php --force
php artisan migrate --path=database/migrations/2026_10_07_000004_create_clinic_appointments_table.php --force
php artisan db:seed --force
```

## Cấu trúc clinic

- `app/Http/Controllers/ClinicController.php`: REST API đặt lịch, tra cứu, trạng thái và slot.
- `app/Models/Clinic*.php`: các model dịch vụ, bác sĩ, khung giờ và lịch hẹn.
- `resources/js/app.js`: Vue application cho bệnh nhân và lễ tân.
- `resources/css/app.css`: giao diện responsive.
- `resources/views/clinic.blade.php`: entrypoint Vue.
- `routes/api.php`: API dưới prefix `/api/clinic`.

## API chính

- `GET /api/clinic/bootstrap`
- `GET /api/clinic/slots`
- `POST /api/clinic/appointments`
- `GET /api/clinic/appointments/lookup`
- `GET /api/clinic/appointments`
- `PATCH /api/clinic/appointments/{id}/status`
- `PATCH /api/clinic/appointments/{id}/note`
- `PATCH /api/clinic/slots/{id}`
