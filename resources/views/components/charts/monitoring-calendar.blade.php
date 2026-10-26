<script>
    /* ============================================================
     * Monitoring FullCalendar & Google Calendar Helper Component
     * ============================================================ */
    function getGoogleCalendarUrl(event) {
        const title = event.title;
        const start = event.start;
        const end = event.end || new Date(start.getTime() + 60 * 60 * 1000);
        const props = event.extendedProps || {};

        const pad = (n) => String(n).padStart(2, '0');
        const format = (d) =>
            `${d.getFullYear()}${pad(d.getMonth()+1)}${pad(d.getDate())}T${pad(d.getHours())}${pad(d.getMinutes())}${pad(d.getSeconds())}`;

        const timeRange = `${format(start)}/${format(end)}`;
        let details = `Petugas: ${props.petugas || '-'}\nStatus: ${props.status || '-'}`;
        let location = `Kantor BPS ${title}`;

        return 'https://calendar.google.com/calendar/render?' + new URLSearchParams({
            action: 'TEMPLATE',
            text: 'HaloKIP - ' + title,
            dates: timeRange,
            details: details,
            location: location,
            ctz: 'Asia/Jakarta'
        }).toString();
    }

    function switchTab(tab) {
        const tabKabkotBtn = document.getElementById('tab-kabkot-btn');
        const tabHariBtn = document.getElementById('tab-hari-btn');
        const tabKabkotContent = document.getElementById('tab-kabkot-content');
        const tabHariContent = document.getElementById('tab-hari-content');

        if (!tabKabkotBtn || !tabHariBtn || !tabKabkotContent || !tabHariContent) return;

        if (tab === 'kabkot') {
            tabKabkotBtn.className =
                "px-5 py-3 text-sm font-bold border-b-2 border-biru1 text-biru1 transition duration-200 cursor-pointer";
            tabHariBtn.className =
                "px-5 py-3 text-sm font-bold border-b-2 border-transparent text-biru3 hover:text-biru1 transition duration-200 cursor-pointer";
            tabKabkotContent.classList.remove('hidden');
            tabHariContent.classList.add('hidden');

            const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
            window.history.pushState({ path: newUrl }, '', newUrl);
        } else {
            tabHariBtn.className =
                "px-5 py-3 text-sm font-bold border-b-2 border-biru1 text-biru1 transition duration-200 cursor-pointer";
            tabKabkotBtn.className =
                "px-5 py-3 text-sm font-bold border-b-2 border-transparent text-biru3 hover:text-biru1 transition duration-200 cursor-pointer";
            tabHariContent.classList.remove('hidden');
            tabKabkotContent.classList.add('hidden');

            if (typeof initCalendar === 'function') {
                initCalendar();
            }

            if (window.visitasiCalendar) {
                setTimeout(() => {
                    window.visitasiCalendar.updateSize();
                }, 50);
            }
            if (window.wawancaraCalendar) {
                setTimeout(() => {
                    window.wawancaraCalendar.updateSize();
                }, 50);
            }

            const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?tab=hari';
            window.history.pushState({ path: newUrl }, '', newUrl);
        }
    }

    function handleTabSwitch() {
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        if (tabParam === 'hari') {
            switchTab('hari');
        } else {
            switchTab('kabkot');
        }
    }

    // Run on init & Livewire navigate
    handleTabSwitch();

    if (!window._monitoringCalendarNavRegistered) {
        window._monitoringCalendarNavRegistered = true;
        document.addEventListener('livewire:navigated', function() {
            const calendarEl = document.getElementById('visitasi-calendar') || document.getElementById('wawancara-calendar');
            if (calendarEl) {
                calendarEl.removeAttribute('data-calendar-initialized');
            }
            handleTabSwitch();
        });
    }
</script>
