/**
 * Async Form Submission & Modal Export Validation Helper
 * Handles fetch POST/GET requests with CSRF token, auto-disabled submit button, and error Toast dispatches
 */

window.submitFormAsync = async function(formElement, onSuccess) {
    if (!formElement) return;
    const formData = new FormData(formElement);
    const submitBtn = formElement.querySelector('button[type="submit"]') || document.querySelector('[x-show*="Modal"] button[type="submit"]');
    
    if (submitBtn) submitBtn.disabled = true;

    try {
        const response = await fetch(formElement.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': formData.get('_token') || document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: formData
        });

        const data = await response.json();

        if (response.ok && data.success) {
            if (typeof onSuccess === 'function') {
                onSuccess(data);
            } else {
                window.location.reload();
            }
        } else {
            let errorText = data.message || 'Terjadi kesalahan.';
            if (data.errors && typeof data.errors === 'object') {
                const firstKey = Object.keys(data.errors)[0];
                if (firstKey && data.errors[firstKey].length > 0) {
                    errorText = data.errors[firstKey][0];
                }
            }
            window.dispatchEvent(new CustomEvent('show-toast', {
                detail: { type: 'error', message: errorText }
            }));
        }
    } catch (err) {
        console.error('[AsyncForm] Request failed:', err);
        window.dispatchEvent(new CustomEvent('show-toast', {
            detail: { type: 'error', message: 'Koneksi gagal atau terjadi kesalahan pada server.' }
        }));
    } finally {
        if (submitBtn) submitBtn.disabled = false;
    }
};

window.handleExportSubmit = async function(event, form, modalId) {
    if (event) event.preventDefault();
    if (!form) return;

    let alertEl = form.querySelector('.export-error-alert');
    if (alertEl) {
        alertEl.classList.add('hidden');
        alertEl.innerText = '';
    }

    const formData = new FormData(form);
    const params = new URLSearchParams();
    for (const [key, value] of formData.entries()) {
        if (key.endsWith('[]')) {
            const vals = formData.getAll(key);
            params.delete(key);
            vals.forEach(v => params.append(key, v));
        } else {
            params.set(key, value);
        }
    }
    params.set('validate', '1');

    try {
        const response = await fetch(form.action + '?' + params.toString());
        const data = await response.json();
        if (!data.success) {
            if (!alertEl) {
                alertEl = document.createElement('div');
                alertEl.className = 'export-error-alert mt-4 mx-7 p-3 bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-xl text-left';
                const footer = form.querySelector('.flex.justify-end');
                if (footer && footer.parentElement) {
                    footer.parentElement.insertBefore(alertEl, footer);
                } else {
                    form.appendChild(alertEl);
                }
            }
            alertEl.innerText = data.message;
            alertEl.classList.remove('hidden');
        } else {
            params.delete('validate');
            window.location.href = form.action + '?' + params.toString();
            setTimeout(() => {
                const targetModal = modalId ? document.getElementById(modalId) : (document.getElementById('modal-export') || document.getElementById('modal-export-visitasi') || document.getElementById('modal-export-wawancara') || document.getElementById('modal-export-kuesioner'));
                if (targetModal) targetModal.classList.add('hidden');
                if (alertEl) alertEl.classList.add('hidden');
            }, 1000);
        }
    } catch (e) {
        console.error('[ExportHelper] Exception:', e);
        form.submit();
    }
};
