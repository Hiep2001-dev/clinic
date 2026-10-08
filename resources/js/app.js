import clinicTemplate from './clinic-template.html?raw';

import { clinicContent } from './clinic-content';

const { createApp, ref, computed, onMounted } = Vue;

const today = new Date().toISOString().slice(0, 10);
const statusLabels = clinicContent.statusLabels;

createApp({
    setup() {
        const view = ref(document.body.dataset.clinicView === 'admin' ? 'admin' : 'patient');
        const isAdmin = computed(() => view.value === 'admin');
        const content = clinicContent;
        const step = ref(1);
        const loading = ref(true);
        const saving = ref(false);
        const toast = ref(null);
        const services = ref([]);
        const doctors = ref([]);
        const slots = ref([]);
        const appointments = ref([]);
        const booking = ref(null);
        const lookupResult = ref(null);
        const lookupCode = ref('');
        const lookupPhone = ref('');
        const filters = ref({ date: today, service_id: '', status: '', search: '' });
        const form = ref({ service_id: '', doctor_id: '', time_slot_id: '', patient_name: '', phone: '', birth_date: '', gender: '', address: '', email: '', symptoms: '' });
        const serviceForm = ref({ name: '', description: '', icon: '✚', is_active: true });
        const doctorForm = ref({ name: '', specialty: '', degree: '', avatar: '', is_active: true });
        const editingServiceId = ref(null);
        const editingDoctorId = ref(null);

        const selectedService = computed(() => services.value.find(item => item.id === Number(form.value.service_id)));
        const selectedDoctor = computed(() => doctors.value.find(item => item.id === Number(form.value.doctor_id)));
        const selectedSlot = computed(() => slots.value.find(item => item.id === Number(form.value.time_slot_id)));
        const stats = computed(() => ({
            total: appointments.value.length,
            pending: appointments.value.filter(item => item.status === 'pending').length,
            confirmed: appointments.value.filter(item => item.status === 'confirmed').length,
            completed: appointments.value.filter(item => item.status === 'completed').length,
            cancelled: appointments.value.filter(item => item.status === 'cancelled').length,
        }));

        const navigate = (url) => { window.location.href = url; };
        const logout = async () => {
            await fetch('/clinic/admin/logout', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' },
            });
            window.location.href = '/clinic/admin/login';
        };
        const notify = (message, type = 'success') => {
            toast.value = { message, type };
            window.setTimeout(() => { toast.value = null; }, 3600);
        };
        const api = async (url, options = {}) => {
            const response = await fetch(url, {
                ...options,
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    ...(options.headers || {}),
                },
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || Object.values(data.errors || {})[0]?.[0] || 'Có lỗi xảy ra.');
            return data;
        };
        const load = async () => {
            try {
                const data = await api('/api/clinic/bootstrap?date=' + filters.value.date);
                services.value = data.services; doctors.value = data.doctors; slots.value = data.slots;
                await loadAppointments();
                if (isAdmin.value) await loadCatalog();
            } catch (error) { notify(error.message, 'error'); } finally { loading.value = false; }
        };
        const loadSlots = async () => {
            try { slots.value = await api('/api/clinic/slots?date=' + filters.value.date); form.value.time_slot_id = ''; }
            catch (error) { notify(error.message, 'error'); }
        };
        const loadAppointments = async () => {
            const params = new URLSearchParams(Object.fromEntries(Object.entries(filters.value).filter(([, value]) => value)));
            appointments.value = await api('/api/clinic/appointments?' + params.toString());
        };
        const loadCatalog = async () => {
            const [allServices, allDoctors] = await Promise.all([api('/api/clinic/services'), api('/api/clinic/doctors')]);
            services.value = allServices;
            doctors.value = allDoctors;
        };
        const saveService = async () => {
            try {
                const endpoint = editingServiceId.value ? '/api/clinic/services/' + editingServiceId.value : '/api/clinic/services';
                const data = await api(endpoint, { method: editingServiceId.value ? 'PATCH' : 'POST', body: JSON.stringify(serviceForm.value) });
                if (editingServiceId.value) Object.assign(services.value.find(item => item.id === editingServiceId.value), data.service);
                else services.value.push(data.service);
                editingServiceId.value = null;
                serviceForm.value = { name: '', description: '', icon: '✚', is_active: true };
                notify('Đã lưu chuyên khoa.');
            } catch (error) { notify(error.message, 'error'); }
        };
        const editService = (service) => { editingServiceId.value = service.id; serviceForm.value = { name: service.name, description: service.description || '', icon: service.icon || '✚', is_active: service.is_active }; };
        const removeService = async (service) => {
            if (!window.confirm('Xóa chuyên khoa này?')) return;
            try { await api('/api/clinic/services/' + service.id, { method: 'DELETE' }); services.value = services.value.filter(item => item.id !== service.id); notify('Đã xóa chuyên khoa.'); }
            catch (error) { notify(error.message, 'error'); }
        };
        const toggleService = async (service) => {
            try { const data = await api('/api/clinic/services/' + service.id, { method: 'PATCH', body: JSON.stringify({ is_active: !service.is_active }) }); Object.assign(service, data.service); notify('Đã cập nhật chuyên khoa.'); }
            catch (error) { notify(error.message, 'error'); }
        };
        const saveDoctor = async () => {
            try {
                const endpoint = editingDoctorId.value ? '/api/clinic/doctors/' + editingDoctorId.value : '/api/clinic/doctors';
                const data = await api(endpoint, { method: editingDoctorId.value ? 'PATCH' : 'POST', body: JSON.stringify(doctorForm.value) });
                if (editingDoctorId.value) Object.assign(doctors.value.find(item => item.id === editingDoctorId.value), data.doctor);
                else doctors.value.push(data.doctor);
                editingDoctorId.value = null;
                doctorForm.value = { name: '', specialty: '', degree: '', avatar: '', is_active: true };
                notify('Đã lưu bác sĩ.');
            } catch (error) { notify(error.message, 'error'); }
        };
        const editDoctor = (doctor) => { editingDoctorId.value = doctor.id; doctorForm.value = { name: doctor.name, specialty: doctor.specialty, degree: doctor.degree || '', avatar: doctor.avatar || '', is_active: doctor.is_active }; };
        const removeDoctor = async (doctor) => {
            if (!window.confirm('Xóa bác sĩ này?')) return;
            try { await api('/api/clinic/doctors/' + doctor.id, { method: 'DELETE' }); doctors.value = doctors.value.filter(item => item.id !== doctor.id); notify('Đã xóa bác sĩ.'); }
            catch (error) { notify(error.message, 'error'); }
        };
        const toggleDoctor = async (doctor) => {
            try { const data = await api('/api/clinic/doctors/' + doctor.id, { method: 'PATCH', body: JSON.stringify({ is_active: !doctor.is_active }) }); Object.assign(doctor, data.doctor); notify('Đã cập nhật bác sĩ.'); }
            catch (error) { notify(error.message, 'error'); }
        };
        const chooseService = (service) => { form.value.service_id = service.id; step.value = 2; };
        const submitBooking = async () => {
            saving.value = true;
            try {
                const data = await api('/api/clinic/appointments', { method: 'POST', body: JSON.stringify(form.value) });
                booking.value = data.appointment; step.value = 4; notify('Lịch hẹn đã được ghi nhận.');
                await loadAppointments();
            } catch (error) { notify(error.message, 'error'); } finally { saving.value = false; }
        };
        const lookup = async () => {
            if (!lookupCode.value.trim() || !lookupPhone.value.trim()) {
                notify('Vui lòng nhập cả mã đặt lịch và số điện thoại.', 'error');
                return;
            }
            const params = new URLSearchParams({ booking_code: lookupCode.value.trim(), phone: lookupPhone.value.trim() });
            try { lookupResult.value = (await api('/api/clinic/appointments/lookup?' + params.toString())).appointment; }
            catch (error) { lookupResult.value = null; notify(error.message, 'error'); }
        };
        const updateStatus = async (appointment, status) => {
            try { const data = await api('/api/clinic/appointments/' + appointment.id + '/status', { method: 'PATCH', body: JSON.stringify({ status }) }); Object.assign(appointment, data.appointment); notify('Đã cập nhật trạng thái.'); }
            catch (error) { notify(error.message, 'error'); }
        };
        const updateNote = async (appointment) => {
            try { await api('/api/clinic/appointments/' + appointment.id + '/note', { method: 'PATCH', body: JSON.stringify({ internal_note: appointment.internal_note }) }); notify('Đã lưu ghi chú.'); }
            catch (error) { notify(error.message, 'error'); }
        };
        const toggleSlot = async (slot) => {
            try { const data = await api('/api/clinic/slots/' + slot.id, { method: 'PATCH', body: JSON.stringify({ is_enabled: !slot.is_enabled }) }); Object.assign(slot, data.slot); notify('Đã cập nhật khung giờ.'); }
            catch (error) { notify(error.message, 'error'); }
        };
        const reset = () => { booking.value = null; step.value = 1; form.value = { service_id: '', doctor_id: '', time_slot_id: '', patient_name: '', phone: '', birth_date: '', gender: '', address: '', email: '', symptoms: '' }; };
        const formatDate = (date) => date ? new Intl.DateTimeFormat('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(new Date(date + 'T00:00:00')) : '';
        const time = (value) => value?.slice(0, 5);
        const qrUrl = computed(() => booking.value ? 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&margin=8&data=' + encodeURIComponent(booking.value.booking_code) : '');

        onMounted(load);
        return { view, isAdmin, content, step, loading, saving, toast, services, doctors, slots, appointments, booking, lookupResult, lookupCode, lookupPhone, filters, form, serviceForm, doctorForm, editingServiceId, editingDoctorId, selectedService, selectedDoctor, selectedSlot, stats, statusLabels, navigate, logout, notify, loadSlots, loadAppointments, chooseService, submitBooking, lookup, updateStatus, updateNote, toggleSlot, loadCatalog, saveService, editService, removeService, toggleService, saveDoctor, editDoctor, removeDoctor, toggleDoctor, reset, formatDate, time, qrUrl };
    },
    template: clinicTemplate
}).mount('#clinic-app');
