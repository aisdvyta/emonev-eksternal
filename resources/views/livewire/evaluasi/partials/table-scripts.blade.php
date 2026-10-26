<script>
    /* ============================================================
     * EXCEL-STYLE TABLE FILTERS & COMPONENT DROPDOWNS
     * ============================================================ */

    function toggleExcelFilter(column) {
        const el = document.getElementById('excel-filter-' + column);
        if (!el) return;
        const isHidden = el.classList.contains('hidden');

        // Close all filters first
        closeAllExcelFilters();

        const wrapper = el.closest('.overflow-x-auto');
        if (isHidden) {
            el.classList.remove('hidden');
            if (wrapper && window.innerWidth >= 768) {
                wrapper.style.overflow = 'visible';
            }
        } else {
            el.classList.add('hidden');
            if (wrapper) wrapper.style.overflow = '';
        }
    }

    function closeExcelFilter(column) {
        const el = document.getElementById('excel-filter-' + column);
        if (el) el.classList.add('hidden');

        // Restore overflow if all are closed
        const activeFilters = document.querySelectorAll('[id^="excel-filter-"]:not(.hidden)');
        if (activeFilters.length === 0) {
            const wrapper = document.querySelector('.overflow-x-auto');
            if (wrapper) wrapper.style.overflow = '';
        }
    }

    function closeAllExcelFilters() {
        document.querySelectorAll('[id^="excel-filter-"]').forEach(el => el.classList.add('hidden'));
        const wrapper = document.querySelector('.overflow-x-auto');
        if (wrapper) wrapper.style.overflow = '';
    }

    function filterExcelList(column, q) {
        const query = q.toLowerCase();
        document.querySelectorAll('.excel-item-' + column).forEach(item => {
            const val = item.dataset.value || '';
            item.style.display = val.includes(query) ? '' : 'none';
        });
    }

    function toggleExcelAll(column, master) {
        document.querySelectorAll('.excel-cb-' + column).forEach(cb => {
            if (cb.parentElement.style.display !== 'none') {
                cb.checked = master.checked;
            }
        });
    }

    // Apply filters & call Livewire component
    function applyExcelFilter(column) {
        if (column === 'periode') {
            const checked = [...document.querySelectorAll('.excel-cb-periode:checked')].map(cb => cb.value);
            const all = document.querySelectorAll('.excel-cb-periode');
            const filterVal = checked.length === all.length ? [] : checked;
            @this.call('setFilterPeriodes', filterVal);
        } else if (column === 'kode_satker') {
            const checked = [...document.querySelectorAll('.excel-cb-kode_satker:checked')].map(cb => cb.value);
            const all = document.querySelectorAll('.excel-cb-kode_satker');
            const filterVal = checked.length === all.length ? [] : checked;
            @this.call('setFilterKodes', filterVal);
        } else if (column === 'nama_satker') {
            const checked = [...document.querySelectorAll('.excel-cb-nama_satker:checked')].map(cb => cb.value);
            const all = document.querySelectorAll('.excel-cb-nama_satker');
            const filterVal = checked.length === all.length ? [] : checked;
            @this.call('setFilterKabkots', filterVal);
        } else if (column === 'skala_penilaian') {
            const checked = [...document.querySelectorAll('.excel-cb-skala_penilaian:checked')].map(cb => cb.value);
            const all = document.querySelectorAll('.excel-cb-skala_penilaian');
            const filterVal = checked.length === all.length ? [] : checked;
            @this.call('setFilterStatuses', filterVal);
        }
        closeExcelFilter(column);
    }

    function resetPenilaianExcelFilters() {
        document.querySelectorAll('[class*="excel-cb-"]').forEach(cb => cb.checked = true);
        document.querySelectorAll('[id^="excel-all-"]').forEach(cb => {
            cb.checked = true;
            cb.indeterminate = false;
        });
    }

    /* Indeterminate state sync on checking individual Excel filter boxes */
    document.addEventListener('change', function(e) {
        const columns = ['periode', 'kode_satker', 'nama_satker', 'skala_penilaian'];
        columns.forEach(col => {
            if (e.target.classList.contains('excel-cb-' + col)) {
                const selector = '.excel-cb-' + col;
                const all = document.querySelectorAll(selector);
                const checked = document.querySelectorAll(selector + ':checked');
                const master = document.getElementById('excel-all-' + col);
                if (master) {
                    master.checked = all.length === checked.length;
                    master.indeterminate = checked.length > 0 && checked.length < all.length;
                }
            }
        });
    });

    /* Close popovers when clicking outside */
    document.addEventListener('click', function(e) {
        // Excel headers
        const relativeHeaders = document.querySelectorAll('th.relative, th div.relative');
        let clickedInside = false;
        relativeHeaders.forEach(th => {
            if (th.contains(e.target)) {
                clickedInside = true;
            }
        });
        if (!clickedInside && !e.target.closest('[id^="excel-filter-"]')) {
            closeAllExcelFilters();
        }
    });

    /* --- Modal Export Helper Functions --- */
    function toggleAllKota(el) {
        document.querySelectorAll('.kota-checkbox').forEach(cb => {
            if (cb.closest('.kota-item').style.display !== 'none') cb.checked = el.checked;
        });
    }

    function filterKotaList(val) {
        const query = val.toLowerCase();
        document.querySelectorAll('.kota-item').forEach(el => {
            const nama = el.getAttribute('data-nama') || '';
            if (nama.includes(query)) el.style.display = 'flex';
            else el.style.display = 'none';
        });
    }

    function toggleAllPredikat(el) {
        document.querySelectorAll('.predikat-checkbox').forEach(cb => cb.checked = el.checked);
    }

    async function handleExportSubmit(event, form, modalId = 'modal-export') {
        event.preventDefault();
        let alertEl = form.querySelector('.export-error-alert');
        if (alertEl) {
            alertEl.classList.add('hidden');
            alertEl.innerText = '';
        }
        const submitBtn = form.querySelector('button[type="submit"]');
        const origBtnHtml = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg> Menyiapkan...
            `;
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
                    alertEl.className =
                        'export-error-alert mt-4 mx-7 p-3 bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-xl';
                    const footer = form.querySelector('.flex.justify-end');
                    footer.parentElement.insertBefore(alertEl, footer);
                }
                alertEl.innerText = data.message;
                alertEl.classList.remove('hidden');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = origBtnHtml;
                }
            } else {
                params.delete('validate');
                window.location.href = form.action + '?' + params.toString();
                setTimeout(() => {
                    const targetModal = document.getElementById(modalId) || document.getElementById('modal-export');
                    if (targetModal) targetModal.classList.add('hidden');
                    if (alertEl) alertEl.classList.add('hidden');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = origBtnHtml;
                    }
                }, 1200);
            }
        } catch (e) {
            console.error(e);
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = origBtnHtml;
            }
            form.submit();
        }
    }

    // Register the Livewire commit hook globally once to update charts/map after table changes
    if (!window._livewireCommitHookRegistered) {
        window._livewireCommitHookRegistered = true;

        const registerCommitHook = () => {
            Livewire.hook('commit', ({
                component,
                commit,
                respond,
                succeed,
                fail
            }) => {
                succeed(({
                    snapshot,
                    effect
                }) => {
                    setTimeout(() => {
                        if (document.getElementById('perbandingan-list-chart') && typeof safeInitHistogram === 'function') {
                            safeInitHistogram();
                        }
                        if (document.getElementById('map-sebaran') && typeof safeInitMap === 'function') {
                            safeInitMap();
                        }
                    }, 100);
                });
            });
        };

        if (window.Livewire) {
            registerCommitHook();
        } else {
            document.addEventListener('livewire:init', registerCommitHook);
        }
    }
</script>

@include('components.charts.penilaian-histogram')
@include('components.charts.evaluator-map')
