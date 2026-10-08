import adminSlotsTemplate from './admin-slots-template.html?raw';

const { createApp, ref, onMounted, watch } = Vue;

createApp({
    setup() {
        const selectedDate = ref(new Date().toISOString().slice(0, 10));
        const slots = ref([]);
        const loading = ref(false);
        const message = ref(null);
        const newSlot = ref({ date: selectedDate.value, start_time: '09:00', end_time: '09:30', max_patients: 6, is_enabled: true });

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
            loading.value = true;
            try { slots.value = await api('/api/clinic/slots?date=' + selectedDate.value); }
            catch (error) { notify(error.message, 'error'); }
            finally { loading.value = false; }
        };
        const save = async (slot) => {
            try {
                const data = await api('/api/clinic/slots/' + slot.id, {
                    method: 'PATCH',
                    body: JSON.stringify({ date: selectedDate.value, start_time: slot.start_time.slice(0, 5), end_time: slot.end_time.slice(0, 5), max_patients: slot.max_patients, is_enabled: slot.is_enabled }),
                });
                Object.assign(slot, data.slot);
                notify('Đã cập nhật khung giờ.');
            } catch (error) { notify(error.message, 'error'); await load(); }
        };
        const add = async () => {
            try {
                const data = await api('/api/clinic/slots', { method: 'POST', body: JSON.stringify(newSlot.value) });
                if (data.slot.date === selectedDate.value) slots.value.push(data.slot);
                newSlot.value = { date: selectedDate.value, start_time: '09:00', end_time: '09:30', max_patients: 6, is_enabled: true };
                notify('Đã thêm khung giờ.');
            } catch (error) { notify(error.message, 'error'); }
        };
        const remove = async (slot) => {
            if (!window.confirm(`Xóa khung giờ ${slot.start_time.slice(0, 5)} – ${slot.end_time.slice(0, 5)}?`)) return;
            try {
                await api('/api/clinic/slots/' + slot.id, { method: 'DELETE' });
                slots.value = slots.value.filter(item => item.id !== slot.id);
                notify('Đã xóa khung giờ.');
            } catch (error) { notify(error.message, 'error'); }
        };
        const toggle = async (slot) => { slot.is_enabled = !slot.is_enabled; await save(slot); };

        watch(selectedDate, () => { newSlot.value.date = selectedDate.value; load(); });
        onMounted(load);
        return { selectedDate, slots, loading, message, newSlot, load, save, add, remove, toggle };
    },
    template: adminSlotsTemplate,
}).mount('#clinic-slots');