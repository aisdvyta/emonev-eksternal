import '../css/app.css';
import './bootstrap';
import './card-exporter';
import './excel-table-filter';
import './async-form';

// Lazy-load ECharts hanya saat dibutuhkan — tidak memblokir initial page load.
// Gunakan window.loadECharts() dari halaman yang butuh chart,
// atau tunggu event 'echarts:ready' sebelum menginisialisasi chart.
window.loadECharts = function () {
    if (window._echartsPromise) return window._echartsPromise;
    window._echartsPromise = import('echarts').then((mod) => {
        window.echarts = mod.default || mod;
        window.dispatchEvent(new Event('echarts:ready'));
        return window.echarts;
    });
    return window._echartsPromise;
};

// Auto-load ECharts jika halaman memiliki elemen [data-echarts]
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('[data-echarts]')) {
        window.loadECharts();
    }
});

// Re-check setelah Livewire SPA navigate
document.addEventListener('livewire:navigated', () => {
    if (document.querySelector('[data-echarts]') && !window.echarts) {
        window.loadECharts();
    }
});
