import adminCatalogTemplate from './admin-catalog-template.html?raw';

const { createApp, ref, onMounted } = Vue;

createApp({
    setup() {
        const services = ref([]);
        const doctors = ref([]);
        const serviceForm = ref({ name: '', description: '', icon: '✚', is_active: true });
        const doctorForm = ref({ name: '', specialty: '', degree: '', avatar: '', is_active: true });
        const editingService = ref(null);
        const editingDoctor = ref(null);
        const message = ref(null);

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
        const notify = (text, type = 'success') => {
            message.value = { text, type };
            window.setTimeout(() => { message.value = null; }, 3200);
        };
        const load = async () => {
            try {
                [services.value, doctors.value] = await Promise.all([api('/api/clinic/services'), api('/api/clinic/doctors')]);
            } catch (error) { notify(error.message, 'error'); }
        };
        const saveService = async () => {
            try {
                const id = editingService.value?.id;
                const data = await api(id ? `/api/clinic/services/${id}` : '/api/clinic/services', { method: id ? 'PATCH' : 'POST', body: JSON.stringify(serviceForm.value) });
                if (id) Object.assign(editingService.value, data.service); else services.value.push(data.service);
                cancelService(); notify('Đã lưu chuyên khoa.');
            } catch (error) { notify(error.message, 'error'); }
        };
        const editService = (service) => { editingService.value = service; serviceForm.value = { name: service.name, description: service.description || '', icon: service.icon || '✚', is_active: service.is_active }; };
        const cancelService = () => { editingService.value = null; serviceForm.value = { name: '', description: '', icon: '✚', is_active: true }; };
        const toggleService = async (service) => {
            try { const data = await api(`/api/clinic/services/${service.id}`, { method: 'PATCH', body: JSON.stringify({ is_active: !service.is_active }) }); Object.assign(service, data.service); notify('Đã cập nhật trạng thái.'); }
            catch (error) { notify(error.message, 'error'); }
        };
        const removeService = async (service) => {
            if (!window.confirm(`Xóa chuyên khoa ${service.name}?`)) return;
            try { await api(`/api/clinic/services/${service.id}`, { method: 'DELETE' }); services.value = services.value.filter(item => item.id !== service.id); notify('Đã xóa chuyên khoa.'); }
            catch (error) { notify(error.message, 'error'); }
        };
        const saveDoctor = async () => {
            try {
                const id = editingDoctor.value?.id;
                const data = await api(id ? `/api/clinic/doctors/${id}` : '/api/clinic/doctors', { method: id ? 'PATCH' : 'POST', body: JSON.stringify(doctorForm.value) });
                if (id) Object.assign(editingDoctor.value, data.doctor); else doctors.value.push(data.doctor);
                cancelDoctor(); notify('Đã lưu bác sĩ.');
            } catch (error) { notify(error.message, 'error'); }
        };
        const editDoctor = (doctor) => { editingDoctor.value = doctor; doctorForm.value = { name: doctor.name, specialty: doctor.specialty, degree: doctor.degree || '', avatar: doctor.avatar || '', is_active: doctor.is_active }; };
        const cancelDoctor = () => { editingDoctor.value = null; doctorForm.value = { name: '', specialty: '', degree: '', avatar: '', is_active: true }; };
        const toggleDoctor = async (doctor) => {
            try { const data = await api(`/api/clinic/doctors/${doctor.id}`, { method: 'PATCH', body: JSON.stringify({ is_active: !doctor.is_active }) }); Object.assign(doctor, data.doctor); notify('Đã cập nhật trạng thái.'); }
            catch (error) { notify(error.message, 'error'); }
        };
        const removeDoctor = async (doctor) => {
            if (!window.confirm(`Xóa bác sĩ ${doctor.name}?`)) return;
            try { await api(`/api/clinic/doctors/${doctor.id}`, { method: 'DELETE' }); doctors.value = doctors.value.filter(item => item.id !== doctor.id); notify('Đã xóa bác sĩ.'); }
            catch (error) { notify(error.message, 'error'); }
        };

        onMounted(load);
        return { services, doctors, serviceForm, doctorForm, editingService, editingDoctor, message, saveService, editService, cancelService, toggleService, removeService, saveDoctor, editDoctor, cancelDoctor, toggleDoctor, removeDoctor };
    },
    template: adminCatalogTemplate
}).mount('#clinic-catalog');
