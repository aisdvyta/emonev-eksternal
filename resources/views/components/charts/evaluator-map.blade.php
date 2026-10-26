<style>
    .custom-tooltip {
        background: white !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
        padding: 12px 16px !important;
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        color: #0f172a !important;
        opacity: 1 !important;
        pointer-events: none !important;
        z-index: 9999 !important;
    }
    .custom-tooltip::before {
        border-top-color: white !important;
    }
    .leaflet-popup-content-wrapper {
        border-radius: 14px !important;
        box-shadow: 0 12px 20px -3px rgba(0, 0, 0, 0.12), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
        border: 1px solid #e2e8f0 !important;
        padding: 2px !important;
        font-family: 'Plus Jakarta Sans', sans-serif !important;
    }
    .leaflet-popup-content {
        margin: 10px 14px !important;
        line-height: 1.4 !important;
    }
    .leaflet-popup-tip {
        background: white !important;
    }
</style>
<script>
    /* ============================================================
     * Leaflet Map Component (Evaluator & Kabag Overview Map)
     * ============================================================ */
    window._regionScores = @json($regionScores ?? []);
    window._satkerScores = @json($satkerScores ?? []);

    function getMapPredikatColor(score, predikat) {
        var s = parseFloat(score || 0);
        var p = (predikat || '').toUpperCase();
        if (p === 'INFORMATIF' || s >= 90) return '#388E3C';
        if (p === 'MENUJU INFORMATIF' || (s >= 80 && s < 90)) return '#68B92E';
        if (p === 'CUKUP INFORMATIF' || (s >= 60 && s < 80)) return '#4C84B0';
        if (p === 'KURANG INFORMATIF' || (s >= 40 && s < 60)) return '#F7B750';
        if (p === 'TIDAK INFORMATIF' || (s > 0 && s < 40)) return '#E82D1F';
        return '#94a3b8';
    }

    var regionMap = {
        'Surabaya': 'surabaya',
        'Sidoarjo': 'surabaya',
        'Gresik': 'surabaya',
        'Mojokerto': 'surabaya',
        'Malang': 'malang',
        'Batu': 'malang',
        'Madiun': 'mataraman_barat',
        'Pacitan': 'mataraman_barat',
        'Ponorogo': 'mataraman_barat',
        'Magetan': 'mataraman_barat',
        'Ngawi': 'mataraman_barat',
        'Trenggalek': 'mataraman_barat',
        'Nganjuk': 'mataraman_barat',
        'Kediri': 'mataraman_timur',
        'Blitar': 'mataraman_timur',
        'Tulungagung': 'mataraman_timur',
        'Jombang': 'mataraman_timur',
        'Bojonegoro': 'pantura',
        'Tuban': 'pantura',
        'Lamongan': 'pantura',
        'Jember': 'tapal_kuda_tengah',
        'Lumajang': 'tapal_kuda_tengah',
        'Probolinggo': 'tapal_kuda_tengah',
        'Pasuruan': 'tapal_kuda_tengah',
        'Banyuwangi': 'ujung_timur',
        'Bondowoso': 'ujung_timur',
        'Situbondo': 'ujung_timur',
        'Bangkalan': 'madura',
        'Sampang': 'madura',
        'Pamekasan': 'madura',
        'Sumenep': 'madura'
    };

    function _getMapData() {
        var regions = window._regionScores || {};
        var satkerScores = window._satkerScores || {};
        var bridge = document.getElementById('map-data-bridge');
        if (bridge) {
            try {
                var r = bridge.getAttribute('data-region-scores');
                var s = bridge.getAttribute('data-satker-scores');
                if (r) regions = JSON.parse(r);
                if (s) satkerScores = JSON.parse(s);
            } catch (e) {
                console.error("Error parsing map data bridge:", e);
            }
        }
        return { regions: regions, satkerScores: satkerScores };
    }

    function renderEvaluatorGeojsonLayer(map, data) {
        if (!map || !data) return;

        if (window._evaluatorGeojsonLayer) {
            try {
                map.removeLayer(window._evaluatorGeojsonLayer);
            } catch (e) {}
            window._evaluatorGeojsonLayer = null;
        }

        var mapData = _getMapData();
        var regions = mapData.regions;
        var satkerScores = mapData.satkerScores;

        var geojsonLayer = L.geoJSON(data, {
            style: function(feature) {
                var rawName = feature.properties.NAME_2 || '';
                var typeName = feature.properties.TYPE_2 || '';
                var normName = rawName.trim();
                if (typeName === 'Kota') {
                    if (normName.startsWith('Kota')) {
                        normName = 'Kota ' + normName.substring(4).trim();
                    } else {
                        normName = 'Kota ' + normName;
                    }
                } else if (typeName === 'Kabupaten') {
                    if (!normName.startsWith('Kabupaten')) {
                        normName = 'Kabupaten ' + normName;
                    }
                }
                var cleanName = rawName.replace(/Kabupaten|Kota/g, '').trim();
                var satkerData = satkerScores[normName] || satkerScores[rawName] || satkerScores['BPS ' + normName] || satkerScores['BPS ' + rawName];
                var fillColor = '#94a3b8';

                if (satkerData && satkerData.score > 0) {
                    fillColor = getMapPredikatColor(satkerData.score, satkerData.predikat || satkerData.status);
                } else {
                    var regionKey = regionMap[cleanName] || regionMap[rawName] || 'pantura';
                    var regionData = regions[regionKey];
                    if (regionData) {
                        fillColor = getMapPredikatColor(regionData.score, regionData.predikat || regionData.status);
                    }
                }

                return {
                    fillColor: fillColor,
                    weight: 1.5,
                    opacity: 1,
                    color: 'white',
                    fillOpacity: 0.7
                };
            },
            onEachFeature: function(feature, layer) {
                var rawName = feature.properties.NAME_2 || '';
                var typeName = feature.properties.TYPE_2 || '';
                var normName = rawName.trim();
                if (typeName === 'Kota') {
                    if (normName.startsWith('Kota')) {
                        normName = 'Kota ' + normName.substring(4).trim();
                    } else {
                        normName = 'Kota ' + normName;
                    }
                } else if (typeName === 'Kabupaten') {
                    if (!normName.startsWith('Kabupaten')) {
                        normName = 'Kabupaten ' + normName;
                    }
                }
                var cleanName = rawName.replace(/Kabupaten|Kota/g, '').trim();
                var satkerData = satkerScores[normName] || 
                                 satkerScores[rawName] || 
                                 satkerScores[cleanName] || 
                                 satkerScores['BPS ' + normName] || 
                                 satkerScores['BPS ' + rawName] ||
                                 satkerScores['BPS ' + cleanName];
                
                if (!satkerData) {
                    var lowerNorm = normName.toLowerCase();
                    var lowerClean = cleanName.toLowerCase();
                    var lowerRaw = rawName.toLowerCase();
                    for (var sKey in satkerScores) {
                        var kLow = sKey.toLowerCase();
                        if (kLow === lowerNorm || kLow === lowerClean || kLow === lowerRaw || kLow === ('bps ' + lowerNorm) || kLow === ('bps ' + lowerClean)) {
                            satkerData = satkerScores[sKey];
                            break;
                        }
                    }
                }

                var regionKey = regionMap[cleanName] || regionMap[rawName] || 'pantura';
                var regionData = regions[regionKey];

                var scoreVal = '0.00';
                var statusVal = 'BELUM DINILAI';
                var colorVal = '#94a3b8';

                if (satkerData && satkerData.score !== undefined && satkerData.score !== null) {
                    scoreVal = parseFloat(satkerData.score).toFixed(2);
                    statusVal = satkerData.predikat || satkerData.status || (satkerData.score > 0 ? '' : 'BELUM DINILAI');
                    if (!statusVal || statusVal === 'BELUM DINILAI') {
                        statusVal = (satkerData.score > 0) ? '' : 'BELUM DINILAI';
                    }
                    colorVal = getMapPredikatColor(satkerData.score, statusVal);
                } else if (regionData) {
                    scoreVal = parseFloat(regionData.score || 0).toFixed(2);
                    statusVal = regionData.predikat || regionData.status || 'BELUM DINILAI';
                    colorVal = getMapPredikatColor(regionData.score, statusVal);
                }

                var tooltipContent = `
                    <div class="font-sans text-xs">
                        <div class="font-extrabold text-sm mb-1 text-slate-800">${normName}</div>
                        <div class="text-xs text-slate-500 font-semibold mb-1">Wilayah: ${regionData ? (regionData.name || regionKey) : '-'}</div>
                        <div class="flex items-center justify-between mb-1.5 gap-4">
                            <span class="text-slate-500 font-medium">Skor Indeks:</span>
                            <span class="font-black text-sm" style="color: ${colorVal};">${scoreVal}</span>
                        </div>
                        <div class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-bold text-white uppercase tracking-wider" style="background-color: ${colorVal};">
                            ${statusVal}
                        </div>
                    </div>
                `;

                // Bind tooltip dengan sticky: true agar selalu mengikuti kursor saat diarahkan
                layer.bindTooltip(tooltipContent, {
                    className: 'custom-tooltip',
                    direction: 'auto',
                    sticky: true,
                    opacity: 1
                });

                // Juga bind popup agar jika diklik tetap memunculkan popup keterangan
                layer.bindPopup(tooltipContent, {
                    className: 'custom-tooltip'
                });

                layer.on({
                    mouseover: function(e) {
                        var lyr = e.target;
                        lyr.setStyle({
                            fillOpacity: 0.9,
                            weight: 2.5,
                            color: '#334155'
                        });
                        if (!L.Browser.ie && !L.Browser.opera && !L.Browser.edge) {
                            lyr.bringToFront();
                        }
                        lyr.openTooltip();
                    },
                    mousemove: function(e) {
                        var lyr = e.target;
                        if (!lyr.isTooltipOpen()) {
                            lyr.openTooltip();
                        }
                    },
                    mouseout: function(e) {
                        geojsonLayer.resetStyle(e.target);
                    },
                    click: function(e) {
                        e.target.openPopup();
                    }
                });
            }
        }).addTo(map);

        window._evaluatorGeojsonLayer = geojsonLayer;
        map.setView([-7.5360, 112.2384], 8);
    }

    function initEvaluatorMap() {
        var mapContainer = document.getElementById('map-sebaran');
        if (!mapContainer) return;

        // Set up ResizeObserver on map container
        _setupMapObserver(mapContainer);

        // Pre-fetch GeoJSON if not yet loaded
        if (!window._eastJavaGeojson && !window._fetchingEastJavaGeojson) {
            window._fetchingEastJavaGeojson = true;
            fetch('/data/east-java-districts.geojson')
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    window._eastJavaGeojson = data;
                    window._fetchingEastJavaGeojson = false;
                    if (window.myMap) {
                        renderEvaluatorGeojsonLayer(window.myMap, data);
                    }
                })
                .catch(function(err) {
                    window._fetchingEastJavaGeojson = false;
                    console.error("Error fetching geojson:", err);
                });
        }

        // Check if mapContainer is currently visible (non-zero width & height)
        var isVisible = mapContainer.offsetWidth > 0 && mapContainer.offsetHeight > 0;
        if (!isVisible) {
            // Container is hidden (e.g. mobile tab not active).
            // ResizeObserver will trigger initEvaluatorMap as soon as it becomes visible!
            return;
        }

        // If map already exists on this container, just invalidate and re-render
        if (window.myMap && window.myMap.getContainer() === mapContainer) {
            window.myMap.invalidateSize();
            if (window._eastJavaGeojson) {
                renderEvaluatorGeojsonLayer(window.myMap, window._eastJavaGeojson);
            }
            return;
        }

        // Clean any existing map instance
        if (window.myMap) {
            try {
                window.myMap.remove();
            } catch (e) {
                console.error("Error removing old map: ", e);
            }
            window.myMap = null;
        }

        mapContainer.innerHTML = '';
        delete mapContainer._leaflet_id;

        var map = L.map('map-sebaran', {
            preferCanvas: true,
            zoomControl: true,
            scrollWheelZoom: false,
            attributionControl: false,
            bounceAtZoomLimits: false
        }).setView([-7.5360, 112.2384], 8);

        window.myMap = map;

        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 16,
            crossOrigin: true,
            updateWhenZooming: false,
            updateWhenIdle: true,
            keepBuffer: 2
        }).addTo(map);

        if (window._eastJavaGeojson) {
            renderEvaluatorGeojsonLayer(map, window._eastJavaGeojson);
        }

        setTimeout(function() {
            if (window.myMap) window.myMap.invalidateSize();
        }, 150);
    }

    function _setupMapObserver(container) {
        if (!window._evaluatorMapObserver && typeof ResizeObserver !== 'undefined') {
            window._evaluatorMapObserver = new ResizeObserver(function(entries) {
                for (var i = 0; i < entries.length; i++) {
                    var entry = entries[i];
                    if (entry.contentRect.width > 0 && entry.contentRect.height > 0) {
                        if (!window.myMap || !window._evaluatorGeojsonLayer) {
                            if (typeof L !== 'undefined') {
                                initEvaluatorMap();
                            }
                        } else if (window.myMap) {
                            window.myMap.invalidateSize();
                        }
                    }
                }
            });
        }

        if (window._evaluatorMapObserver && container) {
            window._evaluatorMapObserver.disconnect();
            window._evaluatorMapObserver.observe(container);
        }
    }

    if (!window._evaluatorMapResizeListener) {
        window._evaluatorMapResizeListener = function() {
            if (window.myMap && document.getElementById('map-sebaran')) {
                var el = document.getElementById('map-sebaran');
                if (el.offsetWidth > 0 && el.offsetHeight > 0) {
                    window.myMap.invalidateSize();
                }
            }
        };
        window.addEventListener('resize', window._evaluatorMapResizeListener);
    }

    function safeInitMap() {
        if (typeof L !== 'undefined') {
            setTimeout(initEvaluatorMap, 80);
        } else {
            var attempts = 0;
            var interval = setInterval(function() {
                attempts++;
                if (typeof L !== 'undefined') {
                    initEvaluatorMap();
                    clearInterval(interval);
                } else if (attempts > 100) {
                    clearInterval(interval);
                    console.error('[Map] Leaflet gagal dimuat.');
                }
            }, 80);
        }
    }
    window.safeInitMap = safeInitMap;
    window.initEvaluatorMap = initEvaluatorMap;

    safeInitMap();

    if (!window._mapNavRegistered) {
        window._mapNavRegistered = true;
        document.addEventListener('livewire:navigated', function() {
            safeInitMap();
        });
    }
</script>
