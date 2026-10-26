/**
 * Excel-Style Table Filters Helper
 * Used across Livewire tables (Kabag, Evaluator, Operator, Admin)
 */

window.toggleExcelFilter = function(column) {
    const el = document.getElementById('excel-filter-' + column);
    if (!el) return;
    const isHidden = el.classList.contains('hidden');

    window.closeAllExcelFilters();

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
};

window.closeExcelFilter = function(column) {
    const el = document.getElementById('excel-filter-' + column);
    if (el) el.classList.add('hidden');

    const activeFilters = document.querySelectorAll('[id^="excel-filter-"]:not(.hidden)');
    if (activeFilters.length === 0) {
        const wrapper = document.querySelector('.overflow-x-auto');
        if (wrapper) wrapper.style.overflow = '';
    }
};

window.closeAllExcelFilters = function() {
    document.querySelectorAll('[id^="excel-filter-"]').forEach(el => el.classList.add('hidden'));
    const wrapper = document.querySelector('.overflow-x-auto');
    if (wrapper) wrapper.style.overflow = '';
};

window.filterExcelList = function(column, q) {
    const query = q.toLowerCase();
    document.querySelectorAll('.excel-item-' + column).forEach(item => {
        const val = item.dataset.value || '';
        item.style.display = val.includes(query) ? '' : 'none';
    });
};

window.toggleExcelAll = function(column, master) {
    document.querySelectorAll('.excel-cb-' + column).forEach(cb => {
        if (cb.parentElement.style.display !== 'none') {
            cb.checked = master.checked;
        }
    });
};

/* ============================================================
 * Generic Monitoring Table Filter Helpers (monvis, monwaw, monkues, userTable)
 * ============================================================ */
window._genericActiveFilters = window._genericActiveFilters || {};

window.toggleExcelFilter = function(column) {
    const el = document.getElementById('excel-filter-' + column);
    if (!el) return;
    const isHidden = el.classList.contains('hidden');

    window.closeAllExcelFilters();

    if (isHidden) {
        el.classList.remove('hidden');
    } else {
        el.classList.add('hidden');
    }
};

window.closeExcelFilter = function(column) {
    const el = document.getElementById('excel-filter-' + column);
    if (el) el.classList.add('hidden');
};

window.closeAllExcelFilters = function() {
    document.querySelectorAll('[id^="excel-filter-"]').forEach(el => el.classList.add('hidden'));
};

window.filterExcelList = function(column, q) {
    const query = q.toLowerCase();
    document.querySelectorAll('.excel-item-' + column).forEach(item => {
        const val = item.dataset.value || '';
        item.style.display = val.includes(query) ? '' : 'none';
    });
};

window.toggleExcelAll = function(column, master) {
    document.querySelectorAll('.excel-cb-' + column).forEach(cb => {
        if (cb.parentElement.style.display !== 'none') {
            cb.checked = master.checked;
        }
    });
};

/* ============================================================
 * Generic Monitoring Table Filter Helpers (monvis, monwaw, monkues, userTable)
 * ============================================================ */
window._genericActiveFilters = window._genericActiveFilters || {};

window.toggleGenericTableFilter = function(prefix, col) {
    const el = document.getElementById(prefix + '-filter-' + col);
    if (!el) return;
    const isHidden = el.classList.contains('hidden');
    document.querySelectorAll('[id*="-filter-"]').forEach(e => e.classList.add('hidden'));

    if (isHidden) {
        el.classList.remove('hidden');
    } else {
        el.classList.add('hidden');
    }
};

window.closeGenericTableFilter = function(prefix, col) {
    const el = document.getElementById(prefix + '-filter-' + col);
    if (el) el.classList.add('hidden');
};

window.filterGenericTableList = function(prefix, col, query) {
    document.querySelectorAll('.' + prefix + '-item-' + col).forEach(item => {
        const val = item.dataset.value || '';
        item.style.display = val.includes(query.toLowerCase()) ? '' : 'none';
    });
};

window.toggleGenericTableAll = function(prefix, col, cb) {
    if (col === 'tahun') {
        document.querySelectorAll('.' + prefix + '-cb-tahun').forEach(c => { c.checked = cb.checked; });
    } else {
        document.querySelectorAll('.' + prefix + '-cb-' + col).forEach(c => {
            c.checked = cb.checked;
            if (c.closest('label')) c.closest('label').style.display = '';
        });
    }
};

window.applyGenericTableFilter = function(prefix, col, wireMap, wireComp) {
    window._genericActiveFilters[prefix] = window._genericActiveFilters[prefix] || {};
    const checked = Array.from(document.querySelectorAll('.' + prefix + '-cb-' + col + ':checked')).map(c => c.value);
    const all = Array.from(document.querySelectorAll('.' + prefix + '-cb-' + col)).map(c => c.value);

    if (col === 'tahun') {
        const allChecked = document.getElementById(prefix + '-all-tahun')?.checked;
        window._genericActiveFilters[prefix][col] = allChecked ? [] : checked;
    } else {
        window._genericActiveFilters[prefix][col] = checked.length === all.length ? [] : checked;
    }

    const filterVal = window._genericActiveFilters[prefix][col];
    const methodName = wireMap && wireMap[col];

    if (methodName) {
        let comp = wireComp;
        if (!comp || typeof comp.call !== 'function') {
            const tableRoot = document.querySelector('[data-table-prefix="' + prefix + '"]');
            const wireEl = tableRoot ? tableRoot.closest('[wire\\:id]') || tableRoot : null;
            const wireId = wireEl?.getAttribute('wire:id');
            if (wireId && window.Livewire) {
                comp = window.Livewire.find(wireId);
            }
        }

        if (comp) {
            if (typeof comp.call === 'function') {
                comp.call(methodName, filterVal);
            } else if (typeof comp[methodName] === 'function') {
                comp[methodName](filterVal);
            }
        }
    }
    window.closeGenericTableFilter(prefix, col);
};

/* ============================================================
 * Modal Checkbox & Filter Helpers
 * ============================================================ */
window.toggleAllModalItems = function(masterEl, itemCheckboxClass) {
    document.querySelectorAll('.' + itemCheckboxClass).forEach(cb => {
        const container = cb.closest('[data-nama]') || cb.parentElement;
        if (!container || container.style.display !== 'none') {
            cb.checked = masterEl.checked;
        }
    });
};

window.filterModalItems = function(queryVal, itemClass) {
    const query = (queryVal || '').toLowerCase();
    document.querySelectorAll('.' + itemClass).forEach(el => {
        const nama = (el.getAttribute('data-nama') || el.textContent || '').toLowerCase();
        el.style.display = nama.includes(query) ? (el.tagName === 'DIV' || el.tagName === 'LABEL' ? 'flex' : '') : 'none';
    });
};

// Global Listeners for Outside Clicks & Checkbox syncs
if (!window._excelFilterListenersRegistered) {
    window._excelFilterListenersRegistered = true;

    document.addEventListener('click', function(e) {
        // If click is inside any filter dropdown panel, keep it open
        if (e.target.closest('[id*="-filter-"]')) {
            return;
        }

        // If click is on a filter toggle button/header, let the toggle function handle it
        if (e.target.closest('[onclick*="Filter"]') || e.target.closest('[onclick*="filter"]')) {
            return;
        }

        // Close all active filter panels
        document.querySelectorAll('[id*="-filter-"]:not(.hidden)').forEach(el => el.classList.add('hidden'));
    });

    document.addEventListener('change', function(e) {
        // Standard excel filters
        const columns = ['periode', 'kode_satker', 'nama_satker', 'skala_penilaian'];
        columns.forEach(col => {
            if (e.target.classList.contains('excel-cb-' + col) || e.target.classList.contains('excel-cb-skala_penilaian')) {
                const selector = col === 'skala_penilaian' ? '.excel-cb-skala_penilaian' : '.excel-cb-' + col;
                const all = document.querySelectorAll(selector);
                const checked = document.querySelectorAll(selector + ':checked');
                const master = document.getElementById('excel-all-' + col);
                if (master) {
                    master.checked = all.length === checked.length;
                    master.indeterminate = checked.length > 0 && checked.length < all.length;
                }
            }
        });

        // Monitoring tahun checkbox sync
        const prefixes = ['monvis', 'monwaw', 'monkues'];
        prefixes.forEach(p => {
            if (e.target && e.target.classList.contains(p + '-cb-tahun')) {
                const all = Array.from(document.querySelectorAll('.' + p + '-cb-tahun'));
                const allChkbox = document.getElementById(p + '-all-tahun');
                if (allChkbox) allChkbox.checked = all.every(c => c.checked);
            }
        });
    });
}

