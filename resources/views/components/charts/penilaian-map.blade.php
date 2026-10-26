<script>
    (function() {
        window.initOperatorMap = function() {
            var mapContainer = document.getElementById('map-sebaran');
            if (!mapContainer) return;

            if (window.myMap) {
                if (window.myMap.getContainer() === mapContainer) {
                    setTimeout(function() {
                        window.myMap.invalidateSize();
                    }, 100);
                    return;
                }
                try {
                    window.myMap.remove();
                } catch (e) {
                    console.error("Error removing old map: ", e);
                }
                window.myMap = null;
            }

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

            var regions = @json($regionScores);
            var satkerScores = @json($satkerScores);
            var userSatkerName =
                '{{ $submission && $submission->user ? $submission->user->masterSatker->nama_satker ?? '' : Auth::user()->masterSatker->nama_satker ?? '' }}';
            var cleanUserSatker = userSatkerName.replace('BPS', '').trim().toLowerCase();
            var currentRegionKey =
                '{{ $regionMap[$submission && $submission->user ? $submission->user->masterSatker->nama_satker ?? '' : Auth::user()->masterSatker->nama_satker ?? ''] ?? '' }}';

            fetch('/data/east-java-districts.geojson')
                .then(response => response.json())
                .then(data => {
                    if (!document.getElementById('map-sebaran') || window.myMap !== map) return;

                    map.invalidateSize();

                    var geojsonLayer = L.geoJSON(data, {
                        style: function(feature) {
                            var rawName = feature.properties.NAME_2;
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
                            var satkerData = satkerScores[normName] || satkerScores[rawName];
                            var fillColor = '#94a3b8';
                            var isUserSatker = (normName.toLowerCase() === cleanUserSatker.toLowerCase());

                            if (satkerData && satkerData.score > 0) {
                                fillColor = satkerData.color;
                            } else {
                                var regionKey = regionMap[cleanName] || regionMap[rawName] || 'pantura';
                                var regionData = regions[regionKey];
                                if (regionData) {
                                    fillColor = regionData.color;
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
                            var rawName = feature.properties.NAME_2;
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
                            var satkerData = satkerScores[normName] || satkerScores[rawName];
                            var regionKey = regionMap[cleanName] || regionMap[rawName] || 'pantura';
                            var regionData = regions[regionKey];

                            var scoreVal = '0.00';
                            var statusVal = 'BELUM DINILAI';
                            var colorVal = '#94a3b8';

                            if (satkerData && satkerData.score > 0) {
                                scoreVal = parseFloat(satkerData.score).toFixed(2);
                                statusVal = satkerData.status;
                                colorVal = satkerData.color;
                            } else if (regionData) {
                                scoreVal = parseFloat(regionData.score).toFixed(2);
                                statusVal = regionData.status;
                                colorVal = regionData.color;
                            }

                            var isUserSatker = (normName.toLowerCase() === cleanUserSatker.toLowerCase());
                            var suffix = isUserSatker ? ' (Anda)' : '';
                            var tooltipContent = `
                        <div class="font-sans text-xs">
                            <div class="font-extrabold text-sm mb-1 text-slate-800">${normName}${suffix}</div>
                            <div class="text-xs text-slate-500 font-semibold mb-1">Wilayah: ${regionData ? regionData.name : '-'}</div>
                            <div class="flex items-center justify-between mb-1.5 gap-4">
                                <span class="text-slate-500 font-medium">Skor Indeks:</span>
                                <span class="font-black text-sm" style="color: ${colorVal};">${scoreVal}</span>
                            </div>
                            <div class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-bold text-white uppercase tracking-wider mb-2" style="background-color: ${colorVal};">
                                ${statusVal}
                            </div>
                            <p class="text-xs text-slate-500 leading-snug border-t border-slate-100 pt-1.5">${regionData ? regionData.desc : ''}</p>
                        </div>
                    `;

                            layer.bindTooltip(tooltipContent, {
                                className: 'custom-tooltip',
                                direction: 'auto',
                                sticky: true
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
                                },
                                mouseout: function(e) {
                                    geojsonLayer.resetStyle(e.target);
                                },
                                click: function(e) {
                                    if (isUserSatker) {
                                        map.fitBounds(e.target.getBounds(), {
                                            padding: [30, 30]
                                        });
                                    }
                                }
                            });

                            if (isUserSatker) {
                                var bounds = layer.getBounds();
                                var center = bounds.getCenter();
                                var redPinIcon = L.divIcon({
                                    className: '',
                                    html: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><path d="M12 0C7.16 0 3.2 3.96 3.2 8.8c0 6.6 8.8 21.2 8.8 21.2s8.8-14.6 8.8-21.2C20.8 3.96 16.84 0 12 0zm0 13.2a4.4 4.4 0 1 1 0-8.8 4.4 4.4 0 0 1 0 8.8z" fill="#E82D1F"/><circle cx="12" cy="8.8" r="3" fill="white"/></svg>',
                                    iconSize: [24, 32],
                                    iconAnchor: [12, 32],
                                    popupAnchor: [0, -32]
                                });
                                L.marker(center, {
                                        icon: redPinIcon,
                                        zIndexOffset: 1000
                                    })
                                    .bindTooltip(
                                        '<strong style="color:#E82D1F;">📍 Wilayah Anda: ' +
                                        normName + '</strong>', {
                                            className: 'custom-tooltip',
                                            direction: 'top',
                                            permanent: false
                                        })
                                    .addTo(map);
                            }
                        }
                    }).addTo(map);

                    map.setView([-7.5360, 112.2384], 8);

                    setTimeout(function() {
                        map.invalidateSize();
                    }, 200);
                });
        };

        setTimeout(function() {
            window.initOperatorMap();
        }, 150);

        if (window.mapNavigatedListener) {
            document.removeEventListener('livewire:navigated', window.mapNavigatedListener);
        }
        window.mapNavigatedListener = function() {
            setTimeout(function() {
                window.initOperatorMap();
            }, 150);
        };
        document.addEventListener('livewire:navigated', window.mapNavigatedListener);
    })();
</script>
