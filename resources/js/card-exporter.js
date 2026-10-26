/* ============================================================
 * Card Exporter Utilities
 * Shared helper functions for capturing map cards, comparison charts, and donut charts as PNG images.
 * ============================================================ */

function _resolveCSSVars(clonedDoc) {
    var computed = window.getComputedStyle(document.documentElement);
    var sheet = clonedDoc.createElement('style');
    var vars = [];
    for (var i = 0; i < computed.length; i++) {
        var prop = computed[i];
        if (prop.startsWith('--')) {
            var val = computed.getPropertyValue(prop);
            if (val && val.indexOf('oklch') === -1) {
                vars.push(prop + ':' + val);
            }
        }
    }
    sheet.textContent = ':root{' + vars.join(';') + '}';
    clonedDoc.head.appendChild(sheet);
}

function _captureCard(cardId, btn) {
    var card = document.getElementById(cardId);
    if (!card) return;
    var label = btn ? btn.querySelector('.copy-btn-label') : null;
    if (label) label.textContent = 'Menyalin...';
    if (btn) btn.disabled = true;

    if (typeof html2canvas === 'undefined') {
        if (label) label.textContent = 'Gagal (lib)!';
        if (btn) btn.disabled = false;
        setTimeout(function () {
            if (label) label.textContent = 'Salin Gambar';
        }, 2000);
        return;
    }

    if (window.ClipboardItem && navigator.clipboard && navigator.clipboard.write) {
        try {
            var clipboardPromise = new Promise(function (resolve, reject) {
                html2canvas(card, {
                    backgroundColor: '#ffffff',
                    scale: 2,
                    useCORS: true,
                    allowTaint: false,
                    logging: false,
                    width: card.offsetWidth,
                    height: card.offsetHeight,
                    onclone: function (clonedDoc) {
                        _resolveCSSVars(clonedDoc);

                        clonedDoc.querySelectorAll('[id^="btn-copy"], [id^="copy-"]').forEach(function (el) {
                            el.style.display = 'none';
                        });

                        for (var i = clonedDoc.styleSheets.length - 1; i >= 0; i--) {
                            var sheet = clonedDoc.styleSheets[i];
                            try {
                                var rules = sheet.cssRules || sheet.rules;
                                if (!rules) continue;
                                for (var j = rules.length - 1; j >= 0; j--) {
                                    var rule = rules[j];
                                    if (rule.cssText && rule.cssText.indexOf('oklch') !== -1) {
                                        sheet.deleteRule(j);
                                    }
                                }
                            } catch (e) {}
                        }

                        clonedDoc.querySelectorAll('.leaflet-control-container').forEach(function (el) {
                            el.style.display = 'none';
                        });

                        var title = clonedDoc.querySelector('h3');
                        if (title) {
                            title.style.fontFamily = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif';
                            title.style.color = '#0f172a';
                            title.style.fontWeight = '700';
                            title.style.fontSize = '20px';
                            title.style.marginBottom = '4px';
                        }
                        var desc = title ? title.nextElementSibling : null;
                        if (desc) {
                            desc.style.fontFamily = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif';
                            desc.style.color = '#64748b';
                            desc.style.fontSize = '14px';
                            desc.style.fontWeight = '400';
                            desc.style.marginBottom = '16px';
                        }

                        clonedDoc.querySelectorAll('.flex.flex-wrap.gap-x-5').forEach(function (el) {
                            el.style.display = 'block';
                            el.style.width = '100%';
                            el.style.height = 'auto';
                            el.style.paddingTop = '12px';
                            el.style.marginBottom = '16px';
                            el.style.fontFamily = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif';

                            el.querySelectorAll('span.inline-flex').forEach(function (span) {
                                span.style.display = 'inline-block';
                                span.style.marginRight = '16px';
                                span.style.marginBottom = '8px';
                                span.style.verticalAlign = 'middle';
                                span.style.fontSize = '12px';
                                span.style.fontWeight = '700';
                                span.style.color = '#334155';

                                var dot = span.querySelector('span.w-3');
                                if (dot) {
                                    dot.style.display = 'inline-block';
                                    dot.style.width = '12px';
                                    dot.style.height = '12px';
                                    dot.style.borderRadius = '50%';
                                    dot.style.marginRight = '6px';
                                    dot.style.verticalAlign = 'middle';
                                }
                            });
                        });

                        var mapDom = clonedDoc.getElementById('map-sebaran');
                        if (mapDom && mapDom.parentElement) {
                            var mapParent = mapDom.parentElement;
                            mapParent.style.border = '1px solid #e2e8f0';
                            mapParent.style.borderRadius = '8px';
                            mapParent.style.overflow = 'hidden';
                        }

                        clonedDoc.querySelectorAll('#map-sebaran *').forEach(function (el) {
                            var tag = el.tagName.toLowerCase();
                            if (tag === 'path' || tag === 'g') return;

                            var computedStyle = window.getComputedStyle(el);
                            var transform = el.style.transform || computedStyle.transform;
                            if (transform && transform !== 'none') {
                                var x = 0, y = 0, isMatch = false;

                                var matrix3d = transform.match(/^matrix3d\((.+)\)$/);
                                if (matrix3d) {
                                    var values3d = matrix3d[1].split(', ');
                                    x = parseFloat(values3d[12]) || 0;
                                    y = parseFloat(values3d[13]) || 0;
                                    isMatch = true;
                                } else {
                                    var matrix = transform.match(/^matrix\((.+)\)$/);
                                    if (matrix) {
                                        var values = matrix[1].split(', ');
                                        x = parseFloat(values[4]) || 0;
                                        y = parseFloat(values[5]) || 0;
                                        isMatch = true;
                                    }
                                }

                                if (isMatch) {
                                    var currentLeft = parseFloat(el.style.left) || 0;
                                    var currentTop = parseFloat(el.style.top) || 0;
                                    el.style.left = (currentLeft + x) + 'px';
                                    el.style.top = (currentTop + y) + 'px';
                                    el.style.transform = 'none';
                                } else {
                                    var translateRegex = /translate(?:3d)?\(([^,]+)px,\s*([^,)]+)px(?:,\s*[^,)]+)?\)/;
                                    var match = transform.match(translateRegex);
                                    if (match) {
                                        x = parseFloat(match[1]) || 0;
                                        y = parseFloat(match[2]) || 0;
                                        var currentLeftFallback = parseFloat(el.style.left) || 0;
                                        var currentTopFallback = parseFloat(el.style.top) || 0;
                                        el.style.left = (currentLeftFallback + x) + 'px';
                                        el.style.top = (currentTopFallback + y) + 'px';
                                        el.style.transform = 'none';
                                    }
                                }
                            }
                        });
                    }
                }).then(function (canvas) {
                    canvas.toBlob(function (blob) {
                        if (blob) {
                            resolve(blob);
                        } else {
                            reject(new Error('Canvas toBlob failed'));
                        }
                    }, 'image/png');
                }).catch(function (err) {
                    reject(err);
                });
            });

            var item = new ClipboardItem({
                'image/png': clipboardPromise
            });

            navigator.clipboard.write([item]).then(function () {
                if (label) label.textContent = '\u2713 Tersalin!';
                if (btn) btn.style.color = '#388E3C';
                setTimeout(function () {
                    if (label) label.textContent = 'Salin Gambar';
                    if (btn) {
                        btn.style.color = '';
                        btn.disabled = false;
                    }
                }, 2500);
            }).catch(function (err) {
                console.warn('Clipboard write failed:', err);
                if (label) label.textContent = 'Gagal!';
                if (btn) btn.disabled = false;
                setTimeout(function () {
                    if (label) label.textContent = 'Salin Gambar';
                }, 2500);
            });
        } catch (e) {
            console.error('Clipboard error:', e);
            if (label) label.textContent = 'Gagal!';
            if (btn) btn.disabled = false;
            setTimeout(function () {
                if (label) label.textContent = 'Salin Gambar';
            }, 2500);
        }
    } else {
        if (label) label.textContent = 'Tidak didukung';
        if (btn) btn.disabled = false;
        setTimeout(function () {
            if (label) label.textContent = 'Salin Gambar';
        }, 2500);
    }
}

function copyPetaCard(btn) {
    console.log('[Salin Gambar] copyPetaCard dipanggil');
    var label = btn ? btn.querySelector('.copy-btn-label') : null;
    if (label) label.textContent = 'Menyalin...';
    if (btn) btn.disabled = true;

    var mapContainer = document.getElementById('map-sebaran');
    if (!mapContainer || typeof html2canvas === 'undefined') {
        if (label) label.textContent = 'Gagal!';
        if (btn) btn.disabled = false;
        setTimeout(function () {
            if (label) label.textContent = 'Salin Gambar';
        }, 2000);
        return;
    }

    var zoomControls = mapContainer.querySelectorAll('.leaflet-control-container');
    zoomControls.forEach(function (el) {
        el.style.display = 'none';
    });

    html2canvas(mapContainer, {
        backgroundColor: '#f8fafc',
        scale: 2,
        useCORS: true,
        allowTaint: false,
        logging: false,
        onclone: function (clonedDoc) {
            if (typeof _resolveCSSVars === 'function') _resolveCSSVars(clonedDoc);
            clonedDoc.querySelectorAll('#map-sebaran *').forEach(function (el) {
                var tag = el.tagName.toLowerCase();
                if (tag === 'path' || tag === 'g') return;
                var computedStyle = window.getComputedStyle(el);
                var transform = el.style.transform || computedStyle.transform;
                if (transform && transform !== 'none') {
                    var x = 0, y = 0, isMatch = false;
                    var matrix3d = transform.match(/^matrix3d\((.+)\)$/);
                    if (matrix3d) {
                        var values3d = matrix3d[1].split(', ');
                        x = parseFloat(values3d[12]) || 0;
                        y = parseFloat(values3d[13]) || 0;
                        isMatch = true;
                    } else {
                        var matrix = transform.match(/^matrix\((.+)\)$/);
                        if (matrix) {
                            var values = matrix[1].split(', ');
                            x = parseFloat(values[4]) || 0;
                            y = parseFloat(values[5]) || 0;
                            isMatch = true;
                        }
                    }
                    if (isMatch) {
                        var currentLeft = parseFloat(el.style.left) || 0;
                        var currentTop = parseFloat(el.style.top) || 0;
                        el.style.left = (currentLeft + x) + 'px';
                        el.style.top = (currentTop + y) + 'px';
                        el.style.transform = 'none';
                    }
                }
            });
        }
    }).then(function (mapCanvas) {
        zoomControls.forEach(function (el) {
            el.style.display = '';
        });

        var SC = 2;
        var W = 820;
        var PAD = 24;
        var HEADER_H = 64;
        var MAP_H = 340;
        var LEGEND_H = 64;
        var H = PAD + HEADER_H + MAP_H + LEGEND_H + PAD;

        var finalCanvas = document.createElement('canvas');
        finalCanvas.width = W * SC;
        finalCanvas.height = H * SC;
        var ctx = finalCanvas.getContext('2d');
        ctx.scale(SC, SC);

        ctx.fillStyle = '#ffffff';
        ctx.beginPath();
        if (typeof _rrect === 'function') {
            _rrect(ctx, 0, 0, W, H, 14);
        }
        ctx.fill();
        ctx.strokeStyle = '#e2e8f0';
        ctx.lineWidth = 1;
        ctx.beginPath();
        if (typeof _rrect === 'function') {
            _rrect(ctx, 0.5, 0.5, W - 1, H - 1, 14);
        }
        ctx.stroke();

        var hMid = PAD + HEADER_H / 2;
        ctx.fillStyle = '#184D77';
        ctx.font = '800 16px -apple-system, BlinkMacSystemFont, Inter, sans-serif';
        ctx.textBaseline = 'middle';
        ctx.textAlign = 'left';
        ctx.fillText('Peta Sebaran Indeks KIP Jawa Timur', PAD, hMid - 8);

        ctx.fillStyle = '#64748b';
        ctx.font = '500 12px -apple-system, BlinkMacSystemFont, Inter, sans-serif';
        ctx.fillText('Arahkan kursor ke wilayah untuk melihat skor Indeks KIP daerah lain', PAD, hMid + 12);

        var mapY = PAD + HEADER_H;
        ctx.save();
        ctx.beginPath();
        if (typeof _rrect === 'function') {
            _rrect(ctx, PAD, mapY, W - PAD * 2, MAP_H, 8);
            ctx.clip();
        }
        ctx.drawImage(mapCanvas, PAD, mapY, W - PAD * 2, MAP_H);
        ctx.restore();

        ctx.strokeStyle = '#e2e8f0';
        ctx.lineWidth = 1;
        ctx.beginPath();
        if (typeof _rrect === 'function') {
            _rrect(ctx, PAD, mapY, W - PAD * 2, MAP_H, 8);
            ctx.stroke();
        }

        var legends = [
            { text: 'Informatif (90 - 100)', color: '#388E3C' },
            { text: 'Menuju Informatif (80 - 89,9)', color: '#5FB463' },
            { text: 'Cukup Informatif (60 - 79,9)', color: '#FEE9A3' }
        ];
        var legends2 = [
            { text: 'Kurang Informatif (40 - 59,9)', color: '#EB891B' },
            { text: 'Tidak Informatif (< 39,9)', color: '#E82D1F' }
        ];

        var legY1 = mapY + MAP_H + 24;
        var legY2 = legY1 + 24;

        ctx.font = '700 11px -apple-system, BlinkMacSystemFont, Inter, sans-serif';
        ctx.textBaseline = 'middle';
        ctx.textAlign = 'left';

        var gapX = 24;

        function drawLegendRow(rowLegends, yPos) {
            var totalWidth = 0;
            var itemWidths = rowLegends.map(function (l) {
                var w = 12 + 6 + ctx.measureText(l.text).width;
                totalWidth += w;
                return w;
            });
            totalWidth += gapX * (rowLegends.length - 1);

            var currentX = (W - totalWidth) / 2;

            rowLegends.forEach(function (l, i) {
                ctx.fillStyle = l.color;
                ctx.beginPath();
                ctx.arc(currentX + 6, yPos, 6, 0, Math.PI * 2);
                ctx.fill();

                ctx.fillStyle = '#475569';
                ctx.fillText(l.text, currentX + 18, yPos);

                currentX += itemWidths[i] + gapX;
            });
        }

        drawLegendRow(legends, legY1);
        drawLegendRow(legends2, legY2);

        finalCanvas.toBlob(function (blob) {
            if (!blob) {
                if (label) label.textContent = 'Gagal!';
                if (btn) btn.disabled = false;
                setTimeout(function () {
                    if (label) label.textContent = 'Salin Gambar';
                }, 2000);
                return;
            }
            if (window.ClipboardItem && navigator.clipboard && navigator.clipboard.write) {
                navigator.clipboard.write([new ClipboardItem({ 'image/png': blob })])
                    .then(function () {
                        if (label) label.textContent = '\u2713 Tersalin!';
                        if (btn) btn.style.color = '#388E3C';
                        setTimeout(function () {
                            if (label) label.textContent = 'Salin Gambar';
                            if (btn) {
                                btn.style.color = '';
                                btn.disabled = false;
                            }
                        }, 2500);
                    }).catch(function (err) {
                        console.warn('Clipboard write failed:', err);
                        if (label) label.textContent = 'Gagal!';
                        if (btn) btn.disabled = false;
                        setTimeout(function () {
                            if (label) label.textContent = 'Salin Gambar';
                        }, 2500);
                    });
            }
        }, 'image/png');
    }).catch(function (err) {
        console.error('html2canvas error:', err);
        if (label) label.textContent = 'Gagal!';
        if (btn) btn.disabled = false;
        setTimeout(function () {
            if (label) label.textContent = 'Salin Gambar';
        }, 2000);
    });
}

function _rrect(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
}

function copyPerbandinganCard(btn) {
    console.log('[Salin Gambar] copyPerbandinganCard dipanggil');
    var label = btn ? btn.querySelector('.copy-btn-label') : null;
    if (label) label.textContent = 'Menyalin...';
    if (btn) btn.disabled = true;

    var chartInst = window.perbandinganChart || window._histogramChartInstance || window._listChartInstance;

    if (!chartInst) {
        if (label) label.textContent = 'Gagal!';
        if (btn) btn.disabled = false;
        setTimeout(function () {
            if (label) label.textContent = 'Salin Gambar';
        }, 2000);
        return;
    }

    if (window.ClipboardItem && navigator.clipboard && navigator.clipboard.write) {
        try {
            var clipboardPromise = new Promise(function (resolve, reject) {
                chartInst.resize({
                    width: 772,
                    height: 240,
                    silent: true
                });

                var chartDataUrl = chartInst.getDataURL({
                    type: 'png',
                    pixelRatio: 2,
                    backgroundColor: '#ffffff'
                });

                chartInst.resize({
                    width: 'auto',
                    height: 'auto',
                    silent: true
                });

                var img = new Image();
                img.onload = function () {
                    var SC = 2;
                    var W = 820;
                    var PAD = 24;
                    var HEADER_H = 48;
                    var CHART_H = 240;
                    var LEGEND_H = 64;
                    var H = PAD + HEADER_H + CHART_H + LEGEND_H + PAD;

                    var canvas = document.createElement('canvas');
                    canvas.width = W * SC;
                    canvas.height = H * SC;
                    var ctx = canvas.getContext('2d');
                    ctx.scale(SC, SC);

                    ctx.fillStyle = '#ffffff';
                    ctx.beginPath();
                    _rrect(ctx, 0, 0, W, H, 14);
                    ctx.fill();
                    ctx.strokeStyle = '#e2e8f0';
                    ctx.lineWidth = 1;
                    ctx.beginPath();
                    _rrect(ctx, 0.5, 0.5, W - 1, H - 1, 14);
                    ctx.stroke();

                    var hMid = PAD + HEADER_H / 2;

                    var selectEl = document.getElementById('line-chart-select-kabkot');
                    var satkerName = selectEl && selectEl.selectedIndex >= 0 ? selectEl.options[selectEl.selectedIndex].text.trim() : '';
                    var fullTitle = satkerName ? 'Skor SAQ per Indikator (' + satkerName + ')' : 'Skor SAQ per Indikator';
                    ctx.fillStyle = '#0f172a';
                    ctx.font = '800 16px -apple-system, BlinkMacSystemFont, Inter, sans-serif';
                    ctx.textBaseline = 'middle';
                    ctx.textAlign = 'left';
                    ctx.fillText(fullTitle, PAD, hMid);

                    var chartY = PAD + HEADER_H;
                    ctx.drawImage(img, PAD, chartY, W - PAD * 2, CHART_H);

                    var legends = [
                        { text: 'Informatif (90 - 100)', color: '#388E3C' },
                        { text: 'Menuju Informatif (80 - 89,9)', color: '#68B92E' },
                        { text: 'Cukup Informatif (60 - 79,9)', color: '#4C84B0' }
                    ];
                    var legends2 = [
                        { text: 'Kurang Informatif (40 - 59,9)', color: '#F7B750' },
                        { text: 'Tidak Informatif (< 39,9)', color: '#E82D1F' }
                    ];

                    var legY1 = chartY + CHART_H + 20;
                    var legY2 = legY1 + 24;

                    ctx.font = '700 11px -apple-system, BlinkMacSystemFont, Inter, sans-serif';
                    ctx.textBaseline = 'middle';
                    ctx.textAlign = 'left';

                    var gapX = 24;

                    function drawLegendRow(rowLegends, yPos) {
                        var totalWidth = 0;
                        var itemWidths = rowLegends.map(function (l) {
                            var w = 12 + 6 + ctx.measureText(l.text).width;
                            totalWidth += w;
                            return w;
                        });
                        totalWidth += gapX * (rowLegends.length - 1);

                        var currentX = (W - totalWidth) / 2;

                        rowLegends.forEach(function (l, i) {
                            ctx.fillStyle = l.color;
                            ctx.beginPath();
                            ctx.arc(currentX + 6, yPos, 6, 0, Math.PI * 2);
                            ctx.fill();

                            ctx.fillStyle = '#475569';
                            ctx.fillText(l.text, currentX + 18, yPos);

                            currentX += itemWidths[i] + gapX;
                        });
                    }

                    drawLegendRow(legends, legY1);
                    drawLegendRow(legends2, legY2);

                    canvas.toBlob(function (blob) {
                        if (blob) {
                            resolve(blob);
                        } else {
                            reject(new Error('Canvas toBlob failed'));
                        }
                    }, 'image/png');
                };
                img.onerror = function () {
                    reject(new Error('Image load failed'));
                };
                img.src = chartDataUrl;
            });

            var item = new ClipboardItem({
                'image/png': clipboardPromise
            });

            navigator.clipboard.write([item]).then(function () {
                if (label) label.textContent = '\u2713 Tersalin!';
                if (btn) btn.style.color = '#388E3C';
                setTimeout(function () {
                    if (label) label.textContent = 'Salin Gambar';
                    if (btn) {
                        btn.style.color = '';
                        btn.disabled = false;
                    }
                }, 2500);
            }).catch(function (err) {
                console.warn('Clipboard write failed:', err);
                if (label) label.textContent = 'Gagal!';
                if (btn) btn.disabled = false;
                setTimeout(function () {
                    if (label) label.textContent = 'Salin Gambar';
                }, 2500);
            });
        } catch (e) {
            console.error('Clipboard error:', e);
            if (label) label.textContent = 'Gagal!';
            if (btn) btn.disabled = false;
            setTimeout(function () {
                if (label) label.textContent = 'Salin Gambar';
            }, 2500);
        }
    } else {
        if (label) label.textContent = 'Tidak didukung';
        if (btn) btn.disabled = false;
        setTimeout(function () {
            if (label) label.textContent = 'Salin Gambar';
        }, 2000);
    }
}

function copyDonutCard(btn) {
    console.log('[Salin Gambar] copyDonutCard dipanggil');
    var label = btn ? btn.querySelector('.copy-btn-label') : null;
    if (label) label.textContent = 'Menyalin...';
    if (btn) btn.disabled = true;

    var chartInst = window._donutChartInstance;
    if (!chartInst) {
        if (label) label.textContent = 'Gagal!';
        if (btn) btn.disabled = false;
        setTimeout(function () {
            if (label) label.textContent = 'Salin Gambar';
        }, 2000);
        return;
    }

    var selectPeriodEl = document.getElementById('donut-period-select');
    var periodText = selectPeriodEl && selectPeriodEl.selectedIndex >= 0 ?
        selectPeriodEl.options[selectPeriodEl.selectedIndex].text.trim() : '';

    var totalCount = document.getElementById('donut-total-count')?.textContent || '0';

    var chartDataUrl = chartInst.getDataURL({
        type: 'png',
        pixelRatio: 2,
        backgroundColor: '#ffffff'
    });

    var img = new Image();
    img.onload = function () {
        var SC = 2;
        var W = 340;
        var PAD = 32;
        var H = 500;

        var canvas = document.createElement('canvas');
        canvas.width = W * SC;
        canvas.height = H * SC;
        var ctx = canvas.getContext('2d');
        ctx.scale(SC, SC);

        ctx.fillStyle = '#ffffff';
        ctx.beginPath();
        _rrect(ctx, 0, 0, W, H, 14);
        ctx.fill();
        ctx.strokeStyle = '#e2e8f0';
        ctx.lineWidth = 1;
        ctx.beginPath();
        _rrect(ctx, 0.5, 0.5, W - 1, H - 1, 14);
        ctx.stroke();

        var headerY = PAD + 10;
        ctx.fillStyle = '#184D77';
        ctx.font = '700 12px -apple-system, BlinkMacSystemFont, Inter, sans-serif';
        ctx.textBaseline = 'middle';
        ctx.textAlign = 'left';
        ctx.fillText('Distribusi Predikat KIP', PAD, headerY);

        if (periodText) {
            ctx.font = '700 11.5px -apple-system, BlinkMacSystemFont, Inter, sans-serif';
            var badgeW = ctx.measureText(periodText).width + 22;
            var badgeX = W - PAD - badgeW;
            ctx.fillStyle = '#f8fafc';
            ctx.strokeStyle = '#e2e8f0';
            ctx.lineWidth = 1;
            ctx.beginPath();
            _rrect(ctx, badgeX, headerY - 13, badgeW, 26, 7);
            ctx.fill();
            ctx.stroke();
            ctx.fillStyle = '#1e293b';
            ctx.textAlign = 'center';
            ctx.fillText(periodText, badgeX + badgeW / 2, headerY);
        }

        var chartW = 192;
        var chartH = 192;
        var chartX = (W - chartW) / 2;
        var chartY = PAD + 40;
        ctx.drawImage(img, chartX, chartY, chartW, chartH);

        var cX = W / 2;
        var cY = chartY + chartH / 2;

        ctx.fillStyle = '#0f172a';
        ctx.font = '900 36px -apple-system, BlinkMacSystemFont, Inter, sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(totalCount, cX, cY - 8);

        ctx.fillStyle = '#475569';
        ctx.font = '700 9px -apple-system, BlinkMacSystemFont, Inter, sans-serif';
        ctx.fillText('TOTAL SATKER', cX, cY + 20);

        var lgX = PAD;
        var lgY = chartY + chartH + 16;
        var itemHeight = 26;

        ctx.fillStyle = '#1e293b';
        ctx.font = '700 12px -apple-system, BlinkMacSystemFont, Inter, sans-serif';
        ctx.textAlign = 'left';
        ctx.textBaseline = 'top';
        ctx.fillText('Distribusi Skala Penilaian', lgX, lgY);

        var listStartY = lgY + 20;

        var items = [
            { label: 'Informatif', color: '#388E3C' },
            { label: 'Menuju Informatif', color: '#4c84b0' },
            { label: 'Cukup Informatif', color: '#68B92E' },
            { label: 'Kurang Informatif', color: '#F7B750' },
            { label: 'Tidak Informatif', color: '#E82D1F' },
            { label: 'Belum Dinilai', color: '#94a3b8' }
        ];

        items.forEach(function (item, idx) {
            var itemY = listStartY + idx * itemHeight + itemHeight / 2;

            ctx.fillStyle = item.color;
            ctx.beginPath();
            ctx.arc(lgX + 6, itemY, 5, 0, 2 * Math.PI);
            ctx.fill();

            ctx.fillStyle = '#1e293b';
            ctx.font = '700 11px -apple-system, BlinkMacSystemFont, Inter, sans-serif';
            ctx.textAlign = 'left';
            ctx.textBaseline = 'middle';
            ctx.fillText(item.label, lgX + 20, itemY);

            var countVal = document.getElementById('donut-count-' + idx)?.textContent || '0';
            var pctVal = document.getElementById('donut-pct-' + idx)?.textContent || '(0%)';
            var valString = countVal + ' Satker ' + pctVal;

            ctx.fillStyle = '#475569';
            ctx.font = '700 12px -apple-system, BlinkMacSystemFont, Inter, sans-serif';
            ctx.textAlign = 'right';
            ctx.fillText(valString, W - PAD, itemY);
        });

        canvas.toBlob(function (blob) {
            if (!blob) {
                if (label) label.textContent = 'Gagal!';
                if (btn) btn.disabled = false;
                setTimeout(function () {
                    if (label) label.textContent = 'Salin Gambar';
                }, 2000);
                return;
            }
            if (window.ClipboardItem && navigator.clipboard && navigator.clipboard.write) {
                try {
                    navigator.clipboard.write([new ClipboardItem({ 'image/png': blob })])
                        .then(function () {
                            if (label) label.textContent = '\u2713 Tersalin!';
                            if (btn) btn.style.color = '#388E3C';
                            setTimeout(function () {
                                if (label) label.textContent = 'Salin Gambar';
                                if (btn) {
                                    btn.style.color = '';
                                    btn.disabled = false;
                                }
                            }, 2500);
                        }).catch(function (err) {
                            console.warn('Clipboard write failed:', err);
                            if (label) label.textContent = 'Gagal!';
                            if (btn) btn.disabled = false;
                            setTimeout(function () {
                                if (label) label.textContent = 'Salin Gambar';
                            }, 2500);
                        });
                } catch (e) {
                    if (label) label.textContent = 'Gagal!';
                    if (btn) btn.disabled = false;
                    setTimeout(function () {
                        if (label) label.textContent = 'Salin Gambar';
                    }, 2000);
                }
            } else {
                if (label) label.textContent = 'Tidak didukung';
                if (btn) btn.disabled = false;
                setTimeout(function () {
                    if (label) label.textContent = 'Salin Gambar';
                }, 2500);
            }
        }, 'image/png');
    };
    img.src = chartDataUrl;
}

// Bind to window object for global availability in Blade views
window._resolveCSSVars = _resolveCSSVars;
window._captureCard = _captureCard;
window.copyPetaCard = copyPetaCard;
window._rrect = _rrect;
window.copyPerbandinganCard = copyPerbandinganCard;
window.copyDonutCard = copyDonutCard;
