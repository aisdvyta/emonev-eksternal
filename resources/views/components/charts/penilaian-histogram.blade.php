<script>
    /* ============================================================
     * Histogram per Indikator (Kabkot Filter Enabled - Unified)
     * ============================================================ */
    if (!window._indicators || window._indicators.length === 0) {
        @if(isset($indicators) && !empty($indicators))
            window._indicators = @json($indicators);
        @endif
    }
    if (!window._indicatorScoresPerKabkot || Object.keys(window._indicatorScoresPerKabkot).length === 0) {
        @if(isset($indicatorScoresPerKabkot) && !empty($indicatorScoresPerKabkot))
            window._indicatorScoresPerKabkot = @json($indicatorScoresPerKabkot);
        @endif
    }

    function _syncHistogramBridgeData() {
        const bridge = document.getElementById('chart-data-bridge');
        if (bridge) {
            try {
                const rawInd = bridge.getAttribute('data-indicators');
                const rawScores = bridge.getAttribute('data-indicator-scores-per-kabkot');
                if (rawInd) {
                    const parsedInd = JSON.parse(rawInd);
                    if (Array.isArray(parsedInd) && parsedInd.length > 0) {
                        window._indicators = parsedInd;
                    }
                }
                if (rawScores) {
                    const parsedScores = JSON.parse(rawScores);
                    if (parsedScores && typeof parsedScores === 'object' && Object.keys(parsedScores).length > 0) {
                        window._indicatorScoresPerKabkot = parsedScores;
                    }
                }
            } catch (e) {
                console.error("Error parsing chart data bridge:", e);
            }
        }
    }

    function initHistogram() {
        var dom = document.getElementById('indikator-histogram') || document.getElementById('perbandingan-list-chart');
        if (!dom) return;

        // Setup ResizeObserver for responsive tabs & screen resize
        if (!window._histoObserver && typeof ResizeObserver !== 'undefined') {
            window._histoObserver = new ResizeObserver(function(entries) {
                for (var i = 0; i < entries.length; i++) {
                    var entry = entries[i];
                    if (entry.contentRect.width > 0 && entry.contentRect.height > 0) {
                        if (window._histogramChartInstance && !window._histogramChartInstance.isDisposed()) {
                            window._histogramChartInstance.resize();
                        } else {
                            initHistogram();
                        }
                    }
                }
            });
        }
        if (window._histoObserver && dom) {
            window._histoObserver.disconnect();
            window._histoObserver.observe(dom);
        }

        if (dom.offsetWidth === 0 && dom.offsetHeight === 0) {
            // Container currently hidden; ResizeObserver will init when visible
            return;
        }

        var echartsObj = window.echarts || (typeof echarts !== 'undefined' ? echarts : null);
        if (echartsObj && !echartsObj.init && echartsObj.default) echartsObj = echartsObj.default;
        if (!echartsObj || !echartsObj.init) return;

        _syncHistogramBridgeData();

        var existing = echartsObj.getInstanceByDom(dom);
        if (existing) {
            try {
                echartsObj.dispose(dom);
            } catch (e) {}
        }

        var chart = echartsObj.init(dom);
        window._histogramChartInstance = chart;

        const bridge = document.getElementById('chart-data-bridge');
        let indicators = window._indicators || [];
        let indicatorScoresPerKabkot = window._indicatorScoresPerKabkot || {};
        let currentKabkotKode = bridge ? (bridge.getAttribute('data-chart-kabkot-kode') || '') : '';

        var labels = indicators.map(function(ind) {
            return ind.nama_indikator || ind.nama || ('Indikator ' + (ind.id_indicators || ''));
        });

        function getSelectedScores() {
            var selectEl = document.getElementById('line-chart-select-kabkot');
            var val = (selectEl && selectEl.value) ? selectEl.value : currentKabkotKode;
            var scoresMap = indicatorScoresPerKabkot[val] || indicatorScoresPerKabkot[String(val)] || indicatorScoresPerKabkot[Number(val)] || {};
            return indicators.map(function(ind) {
                var indId = ind.id_indicators ?? ind.id_indikator ?? ind.id;
                return scoresMap[indId] ?? scoresMap[String(indId)] ?? scoresMap[Number(indId)] ?? 0;
            });
        }

        chart.setOption({
            animation: true,
            animationDuration: 900,
            animationEasing: 'cubicOut',
            animationDelay: function(idx) {
                return idx * 45;
            },
            animationDurationUpdate: 750,
            animationEasingUpdate: 'cubicOut',
            animationDelayUpdate: function(idx) {
                return idx * 30;
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
                backgroundColor: 'rgba(255,255,255,0.97)',
                borderWidth: 0,
                shadowBlur: 12,
                shadowColor: 'rgba(0,0,0,0.1)',
                textStyle: { color: '#0f172a', fontSize: 12 },
                formatter: function(params) {
                    var label = params[0].name;
                    var html = '<div style="font-weight:800;margin-bottom:6px;max-width:220px;white-space:normal;">' + label + '</div>';
                    params.forEach(function(p) {
                        html += '<div style="display:flex;align-items:center;gap:8px;">';
                        html += '<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:' + p.color + ';"></span>';
                        html += '<span style="font-weight:600;color:#475569;">' + p.seriesName + ':</span> ';
                        html += '<span style="font-weight:800;color:' + p.color + ';">' + Number(p.value).toFixed(2) + '</span>';
                        html += '</div>';
                    });
                    return html;
                }
            },
            grid: {
                left: 12,
                right: 12,
                top: 18,
                bottom: labels.length > 8 ? 32 : 20,
                containLabel: true
            },
            xAxis: {
                type: 'category',
                data: labels,
                axisLabel: {
                    interval: 0,
                    rotate: labels.length > 8 ? 35 : (labels.length > 5 ? 28 : 0),
                    color: '#64748b',
                    fontWeight: 'bold',
                    fontSize: labels.length > 8 ? 9 : 10,
                    overflow: 'truncate',
                    width: labels.length > 8 ? 75 : 90
                },
                axisLine: { lineStyle: { color: '#e2e8f0' } },
                axisTick: { show: false }
            },
            dataZoom: labels.length > 8 ? [{
                type: 'inside',
                start: 0,
                end: Math.min(100, Math.round((8 / labels.length) * 100))
            }, {
                type: 'slider',
                show: true,
                height: 12,
                bottom: 0,
                borderColor: 'transparent',
                backgroundColor: '#f1f5f9',
                fillerColor: '#cbd5e1',
                handleSize: '100%'
            }] : [],
            yAxis: {
                type: 'value',
                min: 0,
                max: 100,
                interval: 25,
                axisLabel: { color: '#94a3b8', fontWeight: 'bold', formatter: '{value}' },
                splitLine: { lineStyle: { color: '#f1f5f9' } },
                axisLine: { show: false }
            },
            series: [{
                name: 'Skor Indikator',
                type: 'bar',
                data: getSelectedScores(),
                barMaxWidth: 40,
                itemStyle: {
                    color: function(params) {
                        var v = params.value;
                        if (v >= 90) return '#388E3C';
                        if (v >= 80) return '#68B92E';
                        if (v >= 60) return '#4C84B0';
                        if (v >= 40) return '#F7B750';
                        return v > 0 ? '#E82D1F' : '#94a3b8';
                    },
                    borderRadius: [4, 4, 0, 0]
                },
                label: {
                    show: true,
                    position: 'top',
                    formatter: function(p) {
                        return p.value > 0 ? Number(p.value).toFixed(0) : '';
                    },
                    color: '#334155',
                    fontWeight: 'bold',
                    fontSize: 10
                }
            }]
        });

        setTimeout(function() {
            if (chart && !chart.isDisposed()) chart.resize();
        }, 100);

        if (!window._histoResizeHandler) {
            window._histoResizeHandler = function() {
                if (window._histogramChartInstance && !window._histogramChartInstance.isDisposed()) {
                    window._histogramChartInstance.resize();
                }
            };
            window.addEventListener('resize', window._histoResizeHandler);
        }

        updateChartTitle();
    }

    function updateChartTitle() {
        const selectEl = document.getElementById('line-chart-select-kabkot');
        const bridge = document.getElementById('chart-data-bridge');
        let kode = (selectEl && selectEl.value) ? selectEl.value : '';
        if (!kode && bridge) {
            kode = bridge.getAttribute('data-chart-kabkot-kode') || '';
        }
        if (selectEl && kode && selectEl.value !== kode) {
            for (let i = 0; i < selectEl.options.length; i++) {
                if (selectEl.options[i].value === String(kode)) {
                    selectEl.selectedIndex = i;
                    break;
                }
            }
        }
        const satkerName = selectEl && selectEl.selectedIndex >= 0 ? selectEl.options[selectEl.selectedIndex].text : '';
        const satkerLabel = document.getElementById('line-chart-active-satker');
        if (satkerLabel && satkerName) {
            satkerLabel.textContent = satkerName;
        }
    }
    window.updateChartTitle = updateChartTitle;

    function updateLineChartForKabkot(targetKode) {
        _syncHistogramBridgeData();

        const selectEl = document.getElementById('line-chart-select-kabkot');
        const bridge = document.getElementById('chart-data-bridge');
        let kode = targetKode || (selectEl && selectEl.value) || '';
        if (!kode && bridge) {
            kode = bridge.getAttribute('data-chart-kabkot-kode') || '';
        }
        if (selectEl && kode && selectEl.value !== kode) {
            for (let i = 0; i < selectEl.options.length; i++) {
                if (selectEl.options[i].value === String(kode)) {
                    selectEl.selectedIndex = i;
                    break;
                }
            }
        }
        updateChartTitle();

        if (!window._histogramChartInstance || window._histogramChartInstance.isDisposed()) {
            initHistogram();
            return;
        }

        if (window._histogramChartInstance && !window._histogramChartInstance.isDisposed() && window._indicators && kode) {
            const scoresMap = (window._indicatorScoresPerKabkot && (window._indicatorScoresPerKabkot[kode] || window._indicatorScoresPerKabkot[String(kode)] || window._indicatorScoresPerKabkot[Number(kode)])) || {};
            const scores = window._indicators.map(function(ind) {
                const indId = ind.id_indicators ?? ind.id_indikator ?? ind.id;
                return scoresMap[indId] ?? scoresMap[String(indId)] ?? scoresMap[Number(indId)] ?? 0;
            });
            window._histogramChartInstance.setOption({
                animationDurationUpdate: 650,
                animationEasingUpdate: 'cubicOut',
                series: [{
                    data: scores
                }]
            });
        }
    }
    window.updateLineChartForKabkot = updateLineChartForKabkot;
    window.changeLineChartKabkot = updateLineChartForKabkot;

    function safeInitHistogram() {
        if (window.echarts || typeof echarts !== 'undefined') {
            setTimeout(initHistogram, 100);
        } else if (window.loadECharts) {
            window.loadECharts().then(function() {
                setTimeout(initHistogram, 100);
            });
        }
    }
    window.safeInitHistogram = safeInitHistogram;

    if (window.loadECharts) window.loadECharts();
    safeInitHistogram();

    if (!window._histoNavRegistered) {
        window._histoNavRegistered = true;
        document.addEventListener('livewire:navigated', function() {
            safeInitHistogram();
        });
    }
</script>

