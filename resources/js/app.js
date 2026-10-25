// Tanpa framework JS: halaman dirender server-side. Skrip ini hanya mencegah klik ganda pada tombol kirim.
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const submitter = event.submitter;
    // Tombol yang diklik tetap mengirim nilainya (name=action); nonaktifkan setelah event selesai.
    window.setTimeout(() => {
        form.querySelectorAll('button[type="submit"]').forEach((button) => {
            button.disabled = true;
            button.classList.add('opacity-60', 'cursor-not-allowed');
        });
    }, 0);

    if (submitter && submitter.dataset.confirm && !window.confirm(submitter.dataset.confirm)) {
        event.preventDefault();
        window.setTimeout(() => {
            form.querySelectorAll('button[type="submit"]').forEach((button) => {
                button.disabled = false;
                button.classList.remove('opacity-60', 'cursor-not-allowed');
            });
        }, 0);
    }
});
