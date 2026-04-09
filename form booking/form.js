// Tunggu sampai semua elemen DOM dimuat
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Set minimal tanggal adalah hari ini
    const dateInput = document.getElementById('bookingDate');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
    }

    // 2. Menangani pengiriman form
    const bookingForm = document.getElementById('bookingForm');
    const btnSubmit = document.getElementById('btnSubmit');

    bookingForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Mencegah reload halaman
        
        // Ubah tampilan tombol saat proses
        btnSubmit.innerHTML = 'Mengirim Pesanan...';
        btnSubmit.disabled = true;

        // Simulasi proses pengiriman data (delay 1.5 detik)
        setTimeout(() => {
            alert('Terima kasih! Pesanan cuci mobil Anda telah diterima. Kami akan segera menghubungi Anda.');
            
            // Reset form setelah sukses
            bookingForm.reset();
            
            // Kembalikan tombol ke keadaan awal
            btnSubmit.innerHTML = 'Konfirmasi Booking Sekarang';
            btnSubmit.disabled = false;
        }, 1500);
    });
});